# One-off 2018 crawler (Windows paths) of the 80 articles: rows in article, text in articles/<id>.txt
import requests
from selenium import webdriver
from bs4 import BeautifulSoup
import pymysql
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
browser = webdriver.Chrome(executable_path=r"C:\Users\Win of Mac\Downloads\chromedriver.exe")
browser.get('https://health.gvm.com.tw/article_list_tag_吃出健康.html')
for i in range(0, 10):
    soup = BeautifulSoup(browser.page_source, 'html.parser')
    href = soup.find_all(class_='item-content')
    img = soup.find_all(class_='hover-image')
    for h in range(0, 8):
        b = 'https://health.gvm.com.tw/' + href[h].a['href']
        im = img[h].img['src']
        t = href[h].a.text
        sql = "INSERT INTO article (artid, link, title, pic) VALUES (%s, %s, %s, %s)"
        val = (str(i * 8 + h + 1), b, t, im)
        cursor.execute(sql, val)
        print(b, end="\n")
        print(i, end="\n")
        print(t)
        f = open(r'C:\laragon\www\projectphp\articles\\' + str(i * 8 + h + 1) + '.txt', 'w', encoding='UTF-8')
        result2 = requests.get(b)
        result2.encoding = 'utf-8'
        if result2.status_code == requests.codes.ok:
            soup2 = BeautifulSoup(result2.text, 'html.parser')
            title = soup2.find('title')
            content = soup2.find_all(class_='text5')
            print(title.text, end="\n")
            db.commit()
            f.write(title.text)
            for k in content:
                print(k.text, end="\n")
                f.write(k.text)
        f.close()
    browser.find_element_by_xpath('//a[@class="next page-numbers"][1]').click()
browser.close()
