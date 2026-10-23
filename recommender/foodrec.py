# Food recommender (run after data changes): groups similar users with k-means, saves each group's top foods
from sklearn.feature_extraction.text import TfidfVectorizer
import jieba
import numpy as np
from sklearn import cluster, metrics
from sklearn.impute import SimpleImputer
from sklearn.preprocessing import MinMaxScaler
import pymysql
import os

# jieba's default dictionary splits Traditional Chinese words into characters
jieba.set_dictionary(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'dict.txt.big'))

def seg_word(sentence):
    seg_list = jieba.cut(sentence)
    seg_result = []
    for w in seg_list:
        seg_result.append(w)
    return seg_result

corpus = []
db = pymysql.connect(
    host=os.environ.get('DB_HOST', '127.0.0.1'),
    port=int(os.environ.get('DB_PORT', 3306)),
    user=os.environ.get('DB_USER', 'root'),
    password=os.environ.get('DB_PASSWORD', ''),
    database=os.environ.get('DB_NAME', 'healthme'),
    # Encrypted connection when DB_SSL_CA names a CA certificate (Aiven requires it)
    ssl={'ca': os.environ['DB_SSL_CA']} if os.environ.get('DB_SSL_CA') else None,
)
cursor = db.cursor()

# Real ids: they can have gaps
cursor.execute("SELECT userid FROM user ORDER BY userid")
all_users = [row[0] for row in cursor.fetchall()]
user_ids = []  # Grouped users
no_data = []  # No data at all: they stay in group -1
numbers = []  # Age, height, weight, eat-outs a week (NaN if unknown)
for i in all_users:
    cursor.execute("SELECT food FROM eat WHERE userid = %s", (i,))
    food = cursor.fetchall()
    cursor.execute("SELECT userid, vegetarian, meat, vegetable, food, cook, heat, eatout FROM exam "
                   "WHERE userid = %s", (i,))
    exam = cursor.fetchall()
    cursor.execute("SELECT sex, age, height, weight FROM user WHERE userid = %s", (i,))
    personal = cursor.fetchone()

    # Nothing to group on (ex[0] is userid)
    if len(food) == 0 and all(not e for ex in exam for e in ex[1:]) and all(not e for e in personal):
        no_data.append(i)
        continue
    user_ids.append(i)
    sex, age, height, weight = personal

    # Words: foods eaten, quiz answers 1-6 and sex
    words = [f[0] for f in food]
    eatout = np.nan
    for ex in exam:
        words += [str(e) for e in ex[1:7] if e]
        if ex[7] not in (None, ''):
            eatout = float(ex[7])
    if sex:
        words.append(sex)
    corpus.append(' '.join(seg_word(' '.join(words))))

    # Kept as numbers, so that 22 and 23 years count as close
    numbers.append([np.nan if v is None else float(v) for v in (age, height, weight)] + [eatout])

# TF-IDF words plus numbers; k-means for k = 2..10, the best silhouette wins
try:
    # Keep one-character words (蒸, 炸, 肉): the default token_pattern drops them
    tfidf_vectorizer = TfidfVectorizer(max_features=50, token_pattern=r"(?u)\b\w+\b")
    try:
        a = np.asarray(tfidf_vectorizer.fit_transform(corpus).todense())
    except ValueError:
        # No words at all: use the numbers alone
        a = np.zeros((len(corpus), 0))

    # Missing numbers get the column average, then each column is scaled to 0..1
    nums = SimpleImputer(strategy='mean').fit_transform(np.array(numbers, dtype=float))
    if nums.shape[1]:
        a = np.hstack([a, MinMaxScaler().fit_transform(nums)])
    silhouette_avgs = []

    # At most users - 1 groups (silhouette_score's limit)
    ks = range(2, min(11, len(user_ids)))
    for k in ks:
        # Fixed random_state: same data, same groups
        kmeans_fit = cluster.KMeans(n_clusters=k, random_state=0, n_init=10).fit(a)
        cluster_labels = kmeans_fit.labels_
        silhouette_avg = metrics.silhouette_score(a, cluster_labels)
        silhouette_avgs.append(silhouette_avg)
    m = silhouette_avgs.index(max(silhouette_avgs)) + 2
    kmeans_fit = cluster.KMeans(n_clusters=m, random_state=0, n_init=10).fit(a)
    cluster_labels = kmeans_fit.labels_
except ValueError:
    # Can't split (under 3 users, or all alike): everyone in group 0
    m = 1
    cluster_labels = np.zeros(len(user_ids), dtype=int)
for i in range(0, len(cluster_labels)):
    sql = "UPDATE user SET g = %s WHERE userid = %s"
    val = (cluster_labels[i].item(), user_ids[i])
    cursor.execute(sql, val)
for i in no_data:
    cursor.execute("UPDATE user SET g = -1 WHERE userid = %s", (i,))

# Rebuild recfood; eat.meal is Chinese: 早餐 breakfast, 午餐/中餐 lunch, 晚餐 dinner
MEALS = [('早餐',), ('午餐', '中餐'), ('晚餐',)]

# Rescale to 0..1 (1 is best); all equal: all 0
def scaled(values, higher_is_better):
    lo, hi = min(values), max(values)
    if hi == lo:
        return [0.0] * len(values)
    return [(v - lo) / (hi - lo) if higher_is_better else (hi - v) / (hi - lo) for v in values]

# Up to 4 foods for a group and meal: 50% times eaten, 25% low carbon, 25% low calories
def recommend(group, meal_names):
    marks = ', '.join(['%s'] * len(meal_names))
    query = ("SELECT food.foodid, COUNT(*), food.carbon, food.cal FROM eat "
             "JOIN food ON food.foodname = eat.food JOIN user ON user.userid = eat.userid "
             "WHERE eat.meal IN (" + marks + ')')
    cursor.execute(query + " AND user.g = %s GROUP BY food.foodid", (*meal_names, group))
    rows = cursor.fetchall()
    if not rows:
        cursor.execute(query + " GROUP BY food.foodid", meal_names)
        rows = cursor.fetchall()
    if not rows:
        return []
    often = scaled([r[1] for r in rows], True)
    low_carbon = scaled([r[2] for r in rows], False)
    low_cal = scaled([r[3] for r in rows], False)
    scores = [0.5 * n + 0.25 * c + 0.25 * k for n, c, k in zip(often, low_carbon, low_cal)]
    order = sorted(range(len(rows)), key=lambda j: (-scores[j], -rows[j][1], rows[j][0]))
    return [rows[j][0] for j in order[:4]]

cursor.execute("DELETE FROM recfood")
for i in range(-1, m):
    picks = [' '.join(str(f) for f in recommend(i, meal_names)) for meal_names in MEALS]
    cursor.execute("INSERT INTO recfood (g, breakfast, lunch, dinner) VALUES (%s, %s, %s, %s)", (i, *picks))

# One commit: recfood is never seen half written
db.commit()
db.close()
