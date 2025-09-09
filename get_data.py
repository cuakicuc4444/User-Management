from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
import json
import time
import requests
import re
import pymysql
import datetime

def shorten_url(long_url):
    api = "https://tinyurl.com/api-create.php"
    try:
        response = requests.get(api, params={"url": long_url}, timeout=10)
        if response.status_code == 200:
            return response.text
    except:
        pass
    return long_url

url = "https://apkcombo.com/vi/fifa-mobile/com.ea.gp.fifamobile/"

chrome_options = Options()
chrome_options.add_argument('--headless')
chrome_options.add_argument('--disable-gpu')
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('--window-size=1920,1080')
chrome_options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")

driver = webdriver.Chrome(options=chrome_options)
driver.get(url)
time.sleep(5)  

def get_text_by_xpath(xpath):
    try:
        el = driver.find_element(By.XPATH, xpath)
        text = el.text.strip()
        if text.lower() == "apkcombo.com":
            return None
        return text
    except:
        return None

def get_attr_by_xpath(xpath, attr='src'):
    try:
        return driver.find_element(By.XPATH, xpath).get_attribute(attr)
    except:
        return None

def get_all_attr_by_xpath(xpath, attr='src'):
    try:
        return [el.get_attribute(attr) for el in driver.find_elements(By.XPATH, xpath)]
    except:
        return []


icons = get_all_attr_by_xpath("//div[contains(@class,'app-icon')]//img | //img[contains(@class,'icon')] | //div[contains(@class,'avatar')]//img", 'src')
icons += get_all_attr_by_xpath("//div[contains(@class,'app-icon')]//img | //img[contains(@class,'icon')] | //div[contains(@class,'avatar')]//img", 'data-src')
icon = [i for i in set(icons) if i and i.startswith('http')]

raw_screenshots = get_all_attr_by_xpath("//div[contains(@class,'screenshots')]//img | //div[contains(@class,'gallery')]//img", 'src')
screenshots = [s for s in raw_screenshots if s and s.startswith('http') and '1.gif' not in s]

def upscale_google_play_image(url):
    return re.sub(r'w\d+-h\d+', 'w1024-h768', url)
screenshots = [upscale_google_play_image(s) for s in screenshots]

name = get_text_by_xpath("//h1")
alt_name = get_text_by_xpath("//h2") or get_text_by_xpath("//div[contains(@class,'subtitle')]")
if not alt_name:
    try:
        alt_name = driver.find_element(By.XPATH, "//meta[@property='og:title']").get_attribute('content')
    except:
        alt_name = None

version = get_text_by_xpath("//div[contains(text(),'Phiên bản')]/following-sibling::div[1]")

updated_date = get_text_by_xpath("//div[contains(text(),'Cập nhật')]/following-sibling::div[1]")

def parse_vn_date(date_str):
    try:
        return datetime.datetime.strptime(date_str, "%d thg %m, %Y").strftime("%Y-%m-%d")
    except:
        return None

if updated_date:
    updated_date = parse_vn_date(updated_date)

category = get_text_by_xpath("//div[contains(text(),'Thể loại')]/following-sibling::div[1]/a | //div[contains(text(),'Thể loại')]/following-sibling::a[1]")

developer = get_text_by_xpath("//div[contains(text(),'Nhà phát triển')]/following-sibling::div[1]/a | //div[contains(text(),'Nhà phát triển')]/following-sibling::a[1]")

google_play_id = get_text_by_xpath("//div[contains(text(),'Google Play ID')]/following-sibling::div[1]")

size_mb = get_text_by_xpath("//a[contains(@class,'is-success') and contains(@class,'is-fullwidth')]//span[@class='fsize']//span")
if not size_mb:
    size_mb = get_text_by_xpath("//div[contains(text(),'Kích thước')]/following-sibling::div[1]")
if size_mb:
    size_mb = size_mb.replace(',','.').strip()
    match = re.search(r"([\d\.]+)\s*(MB|GB|KB)?", size_mb, re.IGNORECASE)
    if match:
        value = float(match.group(1))
        unit = match.group(2)
        if unit:
            unit = unit.upper()
            if unit == 'GB':
                size_mb = round(value * 1024, 2)
            elif unit == 'KB':
                size_mb = round(value / 1024, 2)
            else:
                size_mb = value
        else:
            size_mb = value
    else:
        size_mb = None

installs = get_text_by_xpath("//div[contains(text(),'Lượt cài đặt')]/following-sibling::div[1]")

description = (
    get_text_by_xpath("//div[contains(@class,'app-desc')] | //div[@id='desc']") or
    get_text_by_xpath("//meta[@name='description']/@content") or
    get_text_by_xpath("//div[contains(@class,'description')] | //div[contains(@class,'desc')] | //section[contains(@class,'desc')] | //section[contains(@class,'description')]")
)

download_link = get_attr_by_xpath("//a[contains(@class,'download-apk') or contains(@href,'/download/') or contains(@href,'/dl/') or contains(text(),'Tải về APK') or contains(text(),'Download APK')]", 'href')
if download_link and not download_link.startswith('http'):
    download_link = 'https://apkcombo.com' + download_link

real_download_link = None
if download_link:
    driver.get(download_link)
    time.sleep(5)
    links = driver.find_elements(By.XPATH, "//a[contains(@href, '/r2?u=')]")
    if links:
        real_download_link = links[0].get_attribute("href")
        if real_download_link and not real_download_link.startswith('http'):
            real_download_link = 'https://apkcombo.com' + real_download_link

short_download_link = shorten_url(real_download_link or download_link)


app = {
    "icon": icon,
    "name": name,
    "version": version,
    "updated_date": updated_date,
    "category": category,
    "developer": developer,
    "google_play_id": google_play_id,
    "size_mb": size_mb,
    "installs": installs,
    "description": description,
    "download_link": short_download_link,
    "screenshots": screenshots
}

print(json.dumps(app, indent=2, ensure_ascii=False))
driver.quit()

conn = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='userdb',
    charset='utf8mb4'
)
cur = conn.cursor()

import json as pyjson
icon_json = pyjson.dumps(app['icon'], ensure_ascii=False)
screenshots_json = pyjson.dumps(app['screenshots'], ensure_ascii=False)

cur.execute("SELECT id FROM app WHERE name=%s LIMIT 1", (app['name'],))
row = cur.fetchone()
if row:
    sql = """
    UPDATE app SET icon=%s, version=%s, updated_date=%s, category=%s, developer=%s, google_play_id=%s, size_mb=%s, installs=%s, description=%s, download_link=%s, screenshots=%s WHERE id=%s
    """
    cur.execute(sql, (
        icon_json, app['version'], app['updated_date'], app['category'], app['developer'], app['google_play_id'],
        app['size_mb'], app['installs'], app['description'], app['download_link'], screenshots_json, row[0]
    ))
else:
    sql = """
    INSERT INTO app (icon, name, version, updated_date, category, developer, google_play_id, size_mb, installs, description, download_link, screenshots)
    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
    """
    cur.execute(sql, (
        icon_json, app['name'], app['version'], app['updated_date'], app['category'], app['developer'], app['google_play_id'],
        app['size_mb'], app['installs'], app['description'], app['download_link'], screenshots_json
    ))

conn.commit()
cur.close()
conn.close()



