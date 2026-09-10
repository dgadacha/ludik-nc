#!/usr/bin/env python3
"""
Sert le dossier dist/ comme le fera Vercel, pour vérifier la démo avant de la
déployer.

La seule subtilité est le réglage `cleanUrls` du vercel.json : une adresse sans
extension, /4-jeux-de-societe, est servie depuis le fichier
4-jeux-de-societe.html. Le serveur de fichiers de Python ne le fait pas, d'où
ces quelques lignes.

Usage :
    python3 tools/demo/serve-demo.py          # http://localhost:8803
    python3 tools/demo/serve-demo.py 9000
"""

import functools
import http.server
import os
import socketserver
import sys

RACINE = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
DOSSIER = os.path.join(RACINE, 'dist')


class Serveur(http.server.SimpleHTTPRequestHandler):
    def translate_path(self, path):
        chemin = super().translate_path(path)
        if os.path.isdir(chemin):
            index = os.path.join(chemin, 'index.html')
            return index if os.path.exists(index) else chemin
        if not os.path.exists(chemin) and not chemin.endswith('.html'):
            avec_extension = chemin + '.html'
            if os.path.exists(avec_extension):
                return avec_extension
        return chemin

    def send_error(self, code, message=None, explain=None):
        """Vercel sert 404.html pour toute adresse absente : on fait pareil,
        sinon on teste en local une page d'erreur que la démo n'aura pas."""
        page = os.path.join(DOSSIER, '404.html')
        if code == 404 and os.path.exists(page):
            with open(page, 'rb') as fichier:
                corps = fichier.read()
            self.send_response(404)
            self.send_header('Content-Type', 'text/html; charset=utf-8')
            self.send_header('Content-Length', str(len(corps)))
            self.end_headers()
            if self.command != 'HEAD':
                self.wfile.write(corps)
            return
        super().send_error(code, message, explain)

    def log_message(self, format, *args):  # noqa: A002
        if '" 200' not in (format % args):
            sys.stderr.write('%s\n' % (format % args))


def main():
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 8803
    if not os.path.isdir(DOSSIER):
        sys.exit('dist/ est absent : lancer d\'abord tools/demo/build-demo.py')

    gestionnaire = functools.partial(Serveur, directory=DOSSIER)
    socketserver.TCPServer.allow_reuse_address = True
    with socketserver.ThreadingTCPServer(('127.0.0.1', port), gestionnaire) as serveur:
        print('Démo statique sur http://localhost:%s' % port, flush=True)
        serveur.serve_forever()


if __name__ == '__main__':
    main()
