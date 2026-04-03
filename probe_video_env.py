import importlib.util
import os
import sys

modules = ["PIL", "imageio", "moviepy", "cv2", "numpy"]
for name in modules:
    print(f"{name}={bool(importlib.util.find_spec(name))}")

paths = [
    r"C:\Program Files\Microsoft\Edge\Application\msedge.exe",
    r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
    r"C:\ffmpeg\bin\ffmpeg.exe",
    r"C:\Program Files\ffmpeg\bin\ffmpeg.exe",
    r"C:\Program Files\Microsoft Office\root\Office16\POWERPNT.EXE",
    r"C:\Program Files (x86)\Microsoft Office\root\Office16\POWERPNT.EXE",
    r"C:\Program Files\Microsoft Office\Office16\POWERPNT.EXE",
    r"C:\Program Files (x86)\Microsoft Office\Office16\POWERPNT.EXE",
]
for path in paths:
    print(f"{path}={os.path.exists(path)}")

print(sys.version)
