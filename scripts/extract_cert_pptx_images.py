import zipfile
import os

src = r'C:\xampp\htdocs\lms\assets\forms\Sample Certificate for LMS.pptx'
out = r'C:\xampp\htdocs\lms\assets\images\cert'
os.makedirs(out, exist_ok=True)

with zipfile.ZipFile(src) as z:
    names = z.namelist()
    media = [n for n in names if n.startswith('ppt/media/')]
    print('media files:', media)
    mapping = {
        'ppt/media/image1.png': 'wave_bottom.png',
        'ppt/media/image3.png': 'wave_lines.png',
        'ppt/media/image5.png': 'lcp_logo.png',
    }
    for src_path, dst_name in mapping.items():
        if src_path not in names:
            print('MISSING', src_path)
            continue
        with z.open(src_path) as f:
            dst = os.path.join(out, dst_name)
            open(dst, 'wb').write(f.read())
            print('saved', dst, os.path.getsize(dst))
