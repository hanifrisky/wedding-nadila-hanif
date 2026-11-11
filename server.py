import os
import sys
import mimetypes
import webbrowser
import threading
import urllib.parse
from http.server import SimpleHTTPRequestHandler
from socketserver import ThreadingTCPServer

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# Ensure comprehensive MIME types
mimetypes.init()
mimetypes.add_type('application/javascript', '.js')
mimetypes.add_type('text/css', '.css')
mimetypes.add_type('application/x-mpegURL', '.m3u')
mimetypes.add_type('application/x-mpegURL', '.m3u8')
mimetypes.add_type('video/mp4', '.mp4')
mimetypes.add_type('font/woff', '.woff')
mimetypes.add_type('font/woff2', '.woff2')
mimetypes.add_type('image/svg+xml', '.svg')
mimetypes.add_type('image/gif', '.gif')
mimetypes.add_type('image/jpeg', '.jpg')
mimetypes.add_type('image/png', '.png')

class WeddingHandler(SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=BASE_DIR, **kwargs)

    def do_GET(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        query = parsed.query

        # Redirect root to /wedding-nadila-hanif/
        if path in ('', '/'):
            self.send_response(302)
            self.send_header('Location', '/wedding-nadila-hanif/')
            self.end_headers()
            return

        # Redirect /undangan to /wedding-nadila-hanif/undangan
        if path.rstrip('/') == '/undangan':
            self.send_response(302)
            self.send_header('Location', '/wedding-nadila-hanif/undangan')
            self.end_headers()
            return

        # Normalise path prefix /wedding-nadila-hanif/
        rel_path = path
        if rel_path.startswith('/wedding-nadila-hanif/'):
            rel_path = rel_path[len('/wedding-nadila-hanif/'):]
        elif rel_path == '/wedding-nadila-hanif':
            self.send_response(301)
            self.send_header('Location', '/wedding-nadila-hanif/')
            self.end_headers()
            return
        elif rel_path.startswith('/'):
            rel_path = rel_path[1:]

        # Check widget requests
        if 'website-element-widget' in rel_path or '_website-element-widget' in path:
            if 'pageIndex=2' in query:
                widget_file = os.path.join(BASE_DIR, 'widget_2.html')
            else:
                widget_file = os.path.join(BASE_DIR, 'widget_1.html')
            if os.path.exists(widget_file):
                self.serve_file(widget_file, 'text/html; charset=utf-8')
                return

        # Check _footer request
        if rel_path.startswith('_footer') or path.startswith('/_footer'):
            footer_file = os.path.join(BASE_DIR, '_footer')
            if os.path.exists(footer_file):
                self.serve_file(footer_file, 'text/html; charset=utf-8')
                return

        # Check SPA routes: cover, undangan, page-2, page-4
        clean_rel = rel_path.rstrip('/')
        if clean_rel in ('', 'undangan', 'page-2', 'page-4'):
            index_path = os.path.join(BASE_DIR, 'index.html')
            self.serve_file(index_path, 'text/html; charset=utf-8')
            return

        # Static assets
        # Map request to local file in BASE_DIR
        local_target = os.path.join(BASE_DIR, rel_path.replace('/', os.sep))
        if os.path.exists(local_target) and os.path.isfile(local_target):
            ctype = self.guess_type(local_target)
            self.serve_file(local_target, ctype)
            return

        # Fallback to super (default handler)
        super().do_GET()

    def serve_file(self, filepath, content_type):
        try:
            stat = os.stat(filepath)
            size = stat.st_size
            
            # Support HTTP Range requests for video/audio seeking
            range_header = self.headers.get('Range')
            if range_header and range_header.startswith('bytes='):
                byte_range = range_header[6:].strip()
                if '-' in byte_range:
                    start_str, end_str = byte_range.split('-', 1)
                    start = int(start_str) if start_str else 0
                    end = int(end_str) if end_str else size - 1
                    end = min(end, size - 1)
                    length = end - start + 1
                    
                    self.send_response(206)
                    self.send_header('Content-Type', content_type)
                    self.send_header('Content-Range', f'bytes {start}-{end}/{size}')
                    self.send_header('Content-Length', str(length))
                    self.send_header('Accept-Ranges', 'bytes')
                    self.end_headers()
                    
                    with open(filepath, 'rb') as f:
                        f.seek(start)
                        self.wfile.write(f.read(length))
                    return

            self.send_response(200)
            self.send_header('Content-Type', content_type)
            self.send_header('Content-Length', str(size))
            self.send_header('Accept-Ranges', 'bytes')
            self.end_headers()
            
            with open(filepath, 'rb') as f:
                while True:
                    chunk = f.read(65536)
                    if not chunk:
                        break
                    self.wfile.write(chunk)
        except Exception as e:
            # Connection closed by client or broken pipe
            pass

    def log_message(self, format, *args):
        # Clean logging
        sys.stderr.write(f"[{self.log_date_time_string()}] {format % args}\n")

def find_available_port(start_port=8000, max_attempts=50):
    import socket
    for port in range(start_port, start_port + max_attempts):
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            if s.connect_ex(('127.0.0.1', port)) != 0:
                return port
    return start_port

def run():
    port = find_available_port(8000)
    server_address = ('127.0.0.1', port)
    
    ThreadingTCPServer.allow_reuse_address = True
    httpd = ThreadingTCPServer(server_address, WeddingHandler)
    
    url = f"http://localhost:{port}/wedding-nadila-hanif/"
    undangan_url = f"http://localhost:{port}/wedding-nadila-hanif/undangan"
    
    print("=" * 65)
    print("  WEDDING NADILA & HANIF - LOCAL CLONE SERVER")
    print("=" * 65)
    print(f"  Server berjalan di: {url}")
    print(f"  Link Undangan Langsung: {undangan_url}")
    print("=" * 65)
    print("  Tekan Ctrl+C di terminal ini untuk mematikan server.")
    print("=" * 65)
    
    # Auto-open browser after 1 second
    threading.Timer(1.0, lambda: webbrowser.open(url)).start()
    
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nMematikan server...")
    finally:
        httpd.server_close()

if __name__ == '__main__':
    run()
