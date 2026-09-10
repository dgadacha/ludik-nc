"""Relève, pour chaque page catégorie, les catégories qu'elle met en avant."""
import json, os, re, hashlib, lxml.html
CATRE = re.compile(r'^https?://www\.ludik\.nc/(\d+)-[^/?#]*$')
cats = json.load(open('cats_full.json'))
listed = {}
for url, c in cats.items():
    f = 'cache/' + hashlib.md5((url + '?n=50').encode()).hexdigest() + '.html'
    if not os.path.exists(f):
        continue
    try:
        d = lxml.html.parse(f).getroot()
        cc = d.xpath('//*[@id="center_column"]')
        if not cc:
            continue
        kids = []
        for a in cc[0].xpath('.//a/@href'):
            m = CATRE.match(a.split('#')[0])
            if m and int(m.group(1)) != c['id']:
                kids.append(int(m.group(1)))
        listed[str(c['id'])] = sorted(set(kids))
    except Exception:
        pass
json.dump(listed, open('listed.json', 'w'))
big = sorted(listed.items(), key=lambda kv: -len(kv[1]))[:22]
ids = {c['id']: c.get('label') for c in cats.values()}
print('pages avec des sous-catégories mises en avant :')
for k, v in big:
    if len(v) > 3:
        print(f"  {k:7s} {str(ids.get(int(k)))[:44]:46s} {len(v)} catégories")
