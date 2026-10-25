# Генерує фото-заглушки для кейсу:
#   original\  — «як з телефона»: 4000×2667, якість 95 (так їх зазвичай вантажать на сайти клієнтів);
#   optimized\ — для легкої версії: 1600 і 800 px, якість 72.
# Запуск: powershell -File tools\make-photos.ps1
param([string]$Out = (Join-Path $PSScriptRoot '..\photos'))

Add-Type -ReferencedAssemblies System.Drawing -TypeDefinition @'
using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Imaging;
using System.Runtime.InteropServices;

public static class PhotoMaker {
    public static Bitmap Make(int w, int h, Color a, Color b, string label, int seed) {
        var bmp = new Bitmap(w, h, PixelFormat.Format24bppRgb);
        var rnd = new Random(seed);
        using (var g = Graphics.FromImage(bmp)) {
            g.SmoothingMode = SmoothingMode.AntiAlias;
            using (var grad = new LinearGradientBrush(new Rectangle(0, 0, w, h), a, b, 35f)) g.FillRectangle(grad, 0, 0, w, h);
            for (int i = 0; i < 160; i++) {
                int r = rnd.Next(w / 40, w / 7);
                using (var br = new SolidBrush(Color.FromArgb(rnd.Next(10, 45), 255, 255, 255)))
                    g.FillEllipse(br, rnd.Next(-r, w), rnd.Next(-r, h), r, r);
            }
            using (var font = new Font("Segoe UI", w / 45f, FontStyle.Bold))
            using (var shadow = new SolidBrush(Color.FromArgb(120, 0, 0, 0)))
            using (var white = new SolidBrush(Color.FromArgb(200, 255, 255, 255))) {
                var size = g.MeasureString(label, font);
                float x = w - size.Width - w / 40f, y = h - size.Height - w / 40f;
                g.DrawString(label, font, shadow, x + w / 400f, y + w / 400f);
                g.DrawString(label, font, white, x, y);
            }
        }
        // Шум сенсора, як на фото з телефона: через нього JPEG погано стискається.
        var data = bmp.LockBits(new Rectangle(0, 0, w, h), ImageLockMode.ReadWrite, PixelFormat.Format24bppRgb);
        var bytes = new byte[data.Stride * h];
        Marshal.Copy(data.Scan0, bytes, 0, bytes.Length);
        for (int i = 0; i < bytes.Length; i++) {
            int v = bytes[i] + rnd.Next(-14, 15);
            bytes[i] = (byte)(v < 0 ? 0 : v > 255 ? 255 : v);
        }
        Marshal.Copy(bytes, 0, data.Scan0, bytes.Length);
        bmp.UnlockBits(data);
        return bmp;
    }

    public static void SaveJpeg(Image img, string path, long quality) {
        var codec = Array.Find(ImageCodecInfo.GetImageEncoders(), c => c.MimeType == "image/jpeg");
        var p = new EncoderParameters(1);
        p.Param[0] = new EncoderParameter(System.Drawing.Imaging.Encoder.Quality, quality);
        img.Save(path, codec, p);
    }

    public static Bitmap Resize(Image src, int w) {
        int h = (int)Math.Round(src.Height * (double)w / src.Width);
        var dst = new Bitmap(w, h, PixelFormat.Format24bppRgb);
        using (var g = Graphics.FromImage(dst)) {
            g.InterpolationMode = InterpolationMode.HighQualityBicubic;
            g.DrawImage(src, 0, 0, w, h);
        }
        return dst;
    }
}
'@

$photos = @(
    @{ Name = 'hero';        Label = 'Фото: майстерня СТО';      A = '#1e293b'; B = '#ea580c' },
    @{ Name = 'diagnostics'; Label = "Фото: комп’ютерна діагностика"; A = '#0f172a'; B = '#2563eb' },
    @{ Name = 'tires';       Label = 'Фото: шиномонтаж';         A = '#18181b'; B = '#52525b' },
    @{ Name = 'oil';         Label = 'Фото: заміна оливи';       A = '#422006'; B = '#ca8a04' },
    @{ Name = 'gallery-1';   Label = 'Фото: бокс №1';            A = '#172554'; B = '#0891b2' },
    @{ Name = 'gallery-2';   Label = 'Фото: підйомник';          A = '#3f3f46'; B = '#f97316' },
    @{ Name = 'gallery-3';   Label = 'Фото: зона очікування';    A = '#14532d'; B = '#65a30d' }
)

$orig = Join-Path $Out 'original'
$opt = Join-Path $Out 'optimized'
New-Item -ItemType Directory -Force $orig, $opt | Out-Null

$seed = 1
foreach ($p in $photos) {
    $img = [PhotoMaker]::Make(4000, 2667, [Drawing.ColorTranslator]::FromHtml($p.A), [Drawing.ColorTranslator]::FromHtml($p.B), $p.Label, $seed++)
    [PhotoMaker]::SaveJpeg($img, (Join-Path $orig "$($p.Name).jpg"), 95)
    foreach ($w in 1600, 800) {
        $small = [PhotoMaker]::Resize($img, $w)
        [PhotoMaker]::SaveJpeg($small, (Join-Path $opt "$($p.Name)-$w.jpg"), 72)
        $small.Dispose()
    }
    $img.Dispose()
}

Get-ChildItem $orig, $opt | Select-Object Directory, Name, @{ n = 'KB'; e = { [math]::Round($_.Length / 1KB) } } | Format-Table -AutoSize
