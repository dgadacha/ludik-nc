"""Récupère le vrai libellé de chaque catégorie (dernier maillon du fil d'Ariane)."""
import json, os, hashlib, lxml.html

cats = json.load(open('cats_full.json'))

def cached(url):
    p = 'cache/' + hashlib.md5((url + '?n=50').encode()).hexdigest() + '.html'
    return p if os.path.exists(p) else None

fixed = miss = 0
for url, c in cats.items():
    f = cached(url)
    if not f:
        miss += 1
        continue
    try:
        d = lxml.html.parse(f).getroot()
        bc = d.xpath('//*[contains(@class,"breadcrumb")]')
        if not bc:
            miss += 1
            continue
        pipes = bc[0].xpath('.//span[contains(@class,"navigation-pipe")]')
        name = ''
        if pipes:
            name = (pipes[-1].tail or '').strip()
        if not name:
            links = bc[0].xpath('.//a')
            if links:
                name = (links[-1].get('title') or links[-1].text_content()).strip()
        if name:
            c['label'] = ' '.join(name.split())
            fixed += 1
        else:
            miss += 1
    except Exception:
        miss += 1

json.dump(cats, open('cats_full.json', 'w'), ensure_ascii=False)
print('libellés récupérés :', fixed, '— non résolus :', miss)
for v in list(cats.values())[:6]:
    print('  ', v['id'], '|', v.get('label', '?'), '|', v['trail'])
