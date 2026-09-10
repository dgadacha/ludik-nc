import json, os, re, time, threading, hashlib, sys
import urllib.request
from concurrent.futures import ThreadPoolExecutor
import lxml.html

CACHE='cache'
UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36'
lock=threading.Lock()

def fetch(url, tries=3):
    p=os.path.join(CACHE,hashlib.md5(url.encode()).hexdigest()+'.html')
    if os.path.exists(p) and os.path.getsize(p)>500:
        return open(p,encoding='utf-8',errors='replace').read()
    for i in range(tries):
        try:
            req=urllib.request.Request(url, headers={'User-Agent':UA,'Accept-Language':'fr-FR,fr;q=0.9'})
            with urllib.request.urlopen(req, timeout=45) as r:
                data=r.read().decode('utf-8','replace')
            open(p,'w',encoding='utf-8').write(data); return data
        except Exception as e:
            if i==tries-1: sys.stderr.write(f'FAIL {url} {e}\n'); return ''
            time.sleep(2*(i+1))
    return ''

PRICE=re.compile(r'([\d\s ]+)F')
CATRE=re.compile(r'^https?://www\.ludik\.nc/(\d+)-[^/?#]*$')

def parse(html):
    d=lxml.html.fromstring(html)
    hc=' '.join(d.xpath('//*[contains(@class,"heading-counter")]//text()'))
    m=re.search(r'sur\s+(\d+)',hc) or re.search(r'a\s+(\d+)\s+produit',hc)
    tot=int(m.group(1)) if m else 0
    t=d.xpath('//title/text()')
    name=re.sub(r'\s*-\s*Ludik\.nc\s*$','',t[0]).strip() if t else ''
    trail=[]
    bc=d.xpath('//*[contains(@class,"breadcrumb")]')
    if bc:
        for a in bc[0].xpath('.//a'):
            n=' '.join(a.text_content().split())
            if n: trail.append({'name':n,'url':a.get('href')})
    prods=[]
    for li in d.xpath('//ul[contains(@class,"product_list")]/li'):
        a=li.xpath('.//a[contains(@class,"product-name-list")]') or li.xpath('.//a[contains(@class,"product_img_link")]')
        if not a: continue
        purl=a[0].get('href',''); pid=re.search(r'/(\d+)-',purl)
        img=li.xpath('.//img[contains(@class,"replace-2x")]/@src') or li.xpath('.//img/@src')
        desc=li.xpath('.//p[contains(@class,"product-desc")]')
        pr=li.xpath('.//span[contains(@class,"product-price")]/text()')
        price=None
        if pr:
            mm=PRICE.search(pr[0].replace(' ',' ').replace('\xa0',' '))
            if mm: price=int(re.sub(r'\D','',mm.group(1)) or 0)
        av=li.xpath('.//span[contains(@class,"available-now")]//text()') or li.xpath('.//span[contains(@class,"availability")]//text()')
        prods.append({'id':int(pid.group(1)) if pid else None,
            'name':(a[0].get('title') or ' '.join(a[0].text_content().split())).strip(),
            'url':purl,'img':img[0] if img else None,
            'desc':' '.join(desc[0].text_content().split()) if desc else '',
            'price':price,'avail':' '.join(' '.join(av).split())})
    subs=set()
    cc=d.xpath('//*[@id="center_column"]')
    if cc:
        for a in cc[0].xpath('.//a/@href'):
            mm=CATRE.match(a.split('#')[0])
            if mm: subs.add(a.split('#')[0])
    return name, trail, tot, prods, subs

products={}   # id -> product
cats={}       # url -> {id,name,trail,total,ids}
seen=set()
count=[0]

def work(url):
    html=fetch(url+'?n=50')
    if not html: return []
    name, trail, tot, prods, subs = parse(html)
    allp=list(prods)
    if tot>50:
        for p in range(2,(tot+49)//50+1):
            h=fetch(f'{url}?n=50&p={p}')
            if h: allp+=parse(h)[3]
    m0=CATRE.match(url)
    cid=int(m0.group(1)) if m0 else -abs(hash(url))%100000
    new=[]
    with lock:
        cats[url]={'id':cid,'name':name,'trail':[t['name'] for t in trail],
                   'trail_urls':[t['url'] for t in trail],'total':tot,
                   'ids':[p['id'] for p in allp if p['id']]}
        for it in allp:
            if not it['id']: continue
            k=str(it['id'])
            if k not in products:
                it2=dict(it); it2['cats']=[]; products[k]=it2
            if not products[k].get('desc') and it.get('desc'): products[k]['desc']=it['desc']
            if not products[k].get('price') and it.get('price'): products[k]['price']=it['price']
            if cid not in products[k]['cats']: products[k]['cats'].append(cid)
        for s in subs:
            if s not in seen:
                seen.add(s); new.append(s)
        count[0]+=1
        if count[0]%150==0: print(f'{count[0]} cats — {len(products)} produits', flush=True)
    return new

seeds=[u.split('?')[0] for u in json.load(open('cats.json')) if CATRE.match(u.split('?')[0])]
seeds.append('http://www.ludik.nc/285-librairie')
for s in seeds: seen.add(s)
level=list(dict.fromkeys(seeds))
depth=0
with ThreadPoolExecutor(max_workers=8) as ex:
    while level and depth<6:
        depth+=1
        print(f'=== niveau {depth}: {len(level)} catégories à visiter', flush=True)
        nxt=[]
        for res in ex.map(work, level): nxt+=res
        level=list(dict.fromkeys(nxt))

json.dump(products, open('products.json','w'), ensure_ascii=False)
json.dump(cats, open('cats_full.json','w'), ensure_ascii=False)
print('TOTAL produits:', len(products), '— catégories:', len(cats),
      '— non vides:', sum(1 for c in cats.values() if c['ids']))
