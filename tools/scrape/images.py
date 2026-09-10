import os, re, sys, glob, time, subprocess, threading, urllib.request
from concurrent.futures import ThreadPoolExecutor
OUT='/Users/dylan/Documents/ludik-nc/import/img'
os.makedirs(OUT, exist_ok=True)
UA={'User-Agent':'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36'}
IMG=re.compile(r'http://www\.ludik\.nc/(\d+)-home_default/([^"\']+?\.jpg)')
PROD=re.compile(r'href="http://www\.ludik\.nc/[^"]*?/(\d+)-[^"]*?\.html"[^>]*>\s*<img[^>]*src="http://www\.ludik\.nc/(\d+)-home_default/')

pairs={}
for f in glob.glob('cache/*.html'):
    s=open(f,encoding='utf-8',errors='replace').read()
    for m in re.finditer(r'<a class="product_img_link" href="([^"]+)"[^>]*>\s*<img[^>]*src="http://www\.ludik\.nc/(\d+)-home_default/([^"]+\.jpg)"', s):
        pid=re.search(r'/(\d+)-[^/]*\.html', m.group(1))
        if pid: pairs[int(pid.group(1))]=(m.group(2), m.group(3))
print('images à récupérer:', len(pairs), flush=True)
todo=[(p,i,n) for p,(i,n) in pairs.items() if not os.path.exists(f'{OUT}/{p}.jpg')]
print('manquantes:', len(todo), flush=True)
lock=threading.Lock(); c=[0,0]
def one(t):
    pid, iid, name = t
    dst=f'{OUT}/{pid}.jpg'
    tmp=f'{OUT}/.{pid}.tmp'
    for size in ('large_default','home_default'):
        url=f'http://www.ludik.nc/{iid}-{size}/{name}'
        try:
            r=urllib.request.urlopen(urllib.request.Request(url,headers=UA),timeout=40)
            data=r.read()
            if len(data)<800: continue
            open(tmp,'wb').write(data)
            subprocess.run(['magick',tmp,'-strip','-resize','600x600>','-quality','85',dst],
                           check=True, capture_output=True)
            os.remove(tmp)
            with lock:
                c[0]+=1
                if c[0]%500==0: print(f'{c[0]} ok / {c[1]} échecs', flush=True)
            return
        except Exception as e:
            continue
    with lock: c[1]+=1
with ThreadPoolExecutor(max_workers=6) as ex:
    list(ex.map(one, todo))
print('TERMINE ok=',c[0],'echecs=',c[1])
