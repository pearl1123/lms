import os
import urllib.request

base = r'C:\xampp\htdocs\lms\assets\fonts\cert'
os.makedirs(base, exist_ok=True)

fonts = {
    'HammersmithOne-Regular.ttf': 'https://raw.githubusercontent.com/google/fonts/main/ofl/hammersmithone/HammersmithOne-Regular.ttf',
    'ArchivoBlack-Regular.ttf': 'https://raw.githubusercontent.com/google/fonts/main/ofl/archivoblack/ArchivoBlack-Regular.ttf',
    'Parisienne-Regular.ttf': 'https://raw.githubusercontent.com/google/fonts/main/ofl/parisienne/Parisienne-Regular.ttf',
    'Montserrat-Regular.ttf': 'https://raw.githubusercontent.com/google/fonts/main/ofl/montserrat/static/Montserrat-Regular.ttf',
}

for name, url in fonts.items():
    dst = os.path.join(base, name)
    try:
        urllib.request.urlretrieve(url, dst)
        print('ok', name, os.path.getsize(dst))
    except Exception as e:
        print('fail', name, e)
