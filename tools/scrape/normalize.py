# -*- coding: utf-8 -*-
"""Transforme le scrape brut en catalogue prêt à importer dans PrestaShop.

Deux particularités du site d'origine :
  - les libellés de catégories viennent du fil d'Ariane (les <title> sont farcis de mots-clés) ;
  - près de 1 700 séries BD / romans sont rattachées à plat sous « Librairie ». On les
    replace sous le rayon qui les met en avant (La BD spécialisée, Le Manga, etc.).
"""
import json, re, os, unicodedata, collections

OUT = '/Users/dylan/Documents/ludik-nc/import'
IMG = os.path.join(OUT, 'img')

# racines : le fil d'Ariane ne donne pas leur nom
RACINES = {
    256: 'Jeux de société',
    257: 'Cartes à collectionner',
    809: 'Accessoires de jeu',
    16: 'Jeux de rôle',
    13097: 'Loisirs créatifs',
    285: 'Librairie',
}

def slugify(s, maxlen=110):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode()
    s = re.sub(r'[^a-zA-Z0-9]+', '-', s).strip('-').lower()
    return (re.sub(r'-{2,}', '-', s) or 'x')[:maxlen].strip('-')

cats = json.load(open('cats_full.json'))
prods = json.load(open('products.json'))
listed = json.load(open('listed.json'))

nodes = {}
url2id = {}
for url, c in cats.items():
    cid = c['id']
    label = RACINES.get(cid) or c.get('label') or c['name']
    if label == 'retour à Accueil':
        label = RACINES.get(cid, f'Catégorie {cid}')
    nodes[cid] = {
        'src': cid, 'url': url, 'name': ' '.join(label.split()),
        'trail': c['trail'], 'trail_urls': [u.rstrip('/') for u in c.get('trail_urls', [])],
        'ids': sorted(set(c['ids'])), 'total': c['total'],
    }
    url2id[url.rstrip('/')] = cid

# qui met en avant qui (uniquement les rayons : au moins 8 catégories listées)
rayons = {int(k): v for k, v in listed.items() if len(v) >= 8}
promu_par = collections.defaultdict(list)
for parent, kids in rayons.items():
    for k in kids:
        if k in nodes and k != parent:
            promu_par[k].append(parent)

# parent d'après le fil d'Ariane
for n in nodes.values():
    p = None
    for u in reversed(n['trail_urls']):
        cand = url2id.get(u)
        if cand and cand != n['src']:
            p = cand
            break
    n['parent_trail'] = p

def is_descendant(candidate, of, get_parent, guard=40):
    """candidate est-il déjà sous `of` ? (évite de créer un cycle)"""
    cur, i = candidate, 0
    while cur is not None and i < guard:
        if cur == of:
            return True
        cur = get_parent(cur)
        i += 1
    return False

# parent définitif : on préfère un rayon quand le fil d'Ariane s'arrête à une racine
parents = {n['src']: n['parent_trail'] for n in nodes.values()}
for n in sorted(nodes.values(), key=lambda x: len(x['trail'])):
    cid = n['src']
    pt = n['parent_trail']
    if pt is not None and pt not in RACINES:
        continue                                  # rattachement déjà précis
    for cand in promu_par.get(cid, []):
        if cand == cid or cand not in nodes:
            continue
        if is_descendant(cand, cid, lambda x: parents.get(x)):
            continue
        parents[cid] = cand
        break

for n in nodes.values():
    n['parent'] = parents.get(n['src'])

# profondeur réelle
def depth(cid, guard=40):
    d, cur, i = 0, parents.get(cid), 0
    while cur is not None and i < guard:
        d += 1
        cur = parents.get(cur)
        i += 1
    return d
for n in nodes.values():
    n['depth'] = depth(n['src'])

# ---- produits
def ean_of(url):
    m = re.search(r'-(\d{13})\.html$', url or '')
    return m.group(1) if m else ''

products, sans_img = [], 0
for pid, p in prods.items():
    pid = int(pid)
    name = (p.get('name') or '').strip()
    if not name:
        continue
    has_img = os.path.exists(f'{IMG}/{pid}.jpg')
    sans_img += 0 if has_img else 1
    products.append({
        'src': pid, 'name': name[:250], 'price': int(p.get('price') or 0),
        'desc': (p.get('desc') or '').strip(), 'avail': p.get('avail') or '',
        'ean': ean_of(p.get('url')),
        'cats': [c for c in p.get('cats', []) if c in nodes],
        'img': f'{pid}.jpg' if has_img else None,
        'link': slugify(name),
    })

# ---- ne garder que les catégories utiles
useful = set()
def mark(cid):
    i = 0
    while cid is not None and cid not in useful and i < 40:
        useful.add(cid)
        cid = parents.get(cid)
        i += 1
for n in nodes.values():
    if n['ids']:
        mark(n['src'])
for p in products:
    for c in p['cats']:
        mark(c)

catlist = []
for n in nodes.values():
    if n['src'] not in useful:
        continue
    catlist.append({
        'src': n['src'], 'name': n['name'][:120], 'parent': n['parent'],
        'depth': n['depth'], 'link': slugify(n['name']), 'nb': len(n['ids']),
    })
catlist.sort(key=lambda c: (c['depth'], c['src']))

kept = {c['src'] for c in catlist}
for p in products:
    p['cats'] = [c for c in p['cats'] if c in kept]

os.makedirs(OUT, exist_ok=True)
json.dump({'categories': catlist}, open(f'{OUT}/categories.json', 'w'), ensure_ascii=False)
json.dump(products, open(f'{OUT}/products.json', 'w'), ensure_ascii=False)

print(f'catégories retenues : {len(catlist)} / {len(nodes)}')
print(f'produits            : {len(products)} (sans image : {sans_img})')
print(f'sans catégorie      : {sum(1 for p in products if not p["cats"])}')
print(f'prix à 0            : {sum(1 for p in products if not p["price"])}')
print('profondeurs         :', sorted(collections.Counter(c["depth"] for c in catlist).items()))
print('\narbre du haut :')
by_parent = collections.defaultdict(list)
for c in catlist:
    by_parent[c['parent']].append(c)
def show(pid, ind=0, lim=8):
    for c in sorted(by_parent.get(pid, []), key=lambda x: -x['nb'])[:lim]:
        n = sum(1 for _ in by_parent.get(c['src'], []))
        print('  ' * ind + f"- {c['name'][:46]} ({c['nb']} produits, {n} sous-cat.)")
        if ind < 1:
            show(c['src'], ind + 1, 6)
show(None)
