# Article recommender (run after data changes): saves the user's top 9 unread articles by TF-IDF similarity
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import jieba
import pymysql
import sys
import os

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

# jieba's default dictionary splits Traditional Chinese words into characters
jieba.set_dictionary(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'dict.txt.big'))

def seg_word(sentence):
    seg_list = jieba.cut(sentence)
    seg_result = []
    for w in seg_list:
        seg_result.append(w)
    return seg_result

i = sys.argv[1]
cursor.execute("SELECT articleid FROM readart WHERE userid = %s", (i,))
article_ids = cursor.fetchall()
a = []
for art in article_ids:
    a.append(art[0])
cursor.execute("SELECT artid FROM article ORDER BY artid")
total = [row[0] for row in cursor.fetchall()]

# Skip read ids with no article (older data may hold any id)
a = [art for art in a if art in total]
b = list(set(total) - set(a))
if len(b) == 0:
    # All read: recommend as if none were
    a = []
    b = list(set(total) - set(a))

# Profile: quiz answers 1-6 plus the read articles' text (if empty, every article scores 0)
cursor.execute("SELECT vegetarian, meat, vegetable, food, cook, heat FROM exam WHERE userid = %s", (i,))
quiz = [w for row in cursor.fetchall() for answer in row if answer for w in answer.split(',') if w]
doc = ' '.join(quiz) + ' '
for j in a:
    doc += open(r'articles/' + str(j) + '.txt', 'r', encoding='UTF-8').read()
corpus = [' '.join(seg_word(doc))]
for j in b:
    doc = open(r'articles/' + str(j) + '.txt', 'r', encoding='UTF-8').read()
    seg = seg_word(doc)
    corpus.append(' '.join(seg))

# Words of 2+ characters: grammar words like 的, 是, 了 made unrelated articles look alike
tfidf_vectorizer = TfidfVectorizer(max_features=5000)
tfidf = tfidf_vectorizer.fit_transform(corpus)

# Similarity of the profile (row 0) to each candidate; the 9 most similar are saved
dist = cosine_similarity(tfidf[0], tfidf[1:])[0].tolist()
ranked = sorted(zip(b, dist), key=lambda pair: pair[1], reverse=True)
rec_article = ','.join(str(art) for art, _ in ranked[:9])
cursor.execute("INSERT INTO recarticle (userid, articleid) VALUES (%s, %s) AS new "
               "ON DUPLICATE KEY UPDATE articleid = new.articleid", (i, rec_article))
db.commit()
