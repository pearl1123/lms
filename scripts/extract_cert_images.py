import fitz
import os
from PIL import Image
import io

doc = fitz.open(r'C:\xampp\htdocs\lms\assets\forms\Sample Certificate for LMS.pdf')
p = doc[0]
out = r'C:\xampp\htdocs\lms\assets\images'
os.makedirs(out, exist_ok=True)

mapping = {
    0: 'cert_wave_top.png',
    1: 'cert_wave_bottom.png',
    2: 'cert_logo_lcp.png',
}

for i, img in enumerate(p.get_images()):
    xref = img[0]
    base = doc.extract_image(xref)
    name = mapping.get(i, f'cert_raw_{i}.png')
    path = os.path.join(out, name)
    if base['ext'] in ('jpeg', 'jpg'):
        im = Image.open(io.BytesIO(base['image'])).convert('RGBA')
        im.save(path, 'PNG')
    else:
        open(path, 'wb').write(base['image'])
    print('saved', path, os.path.getsize(path))
