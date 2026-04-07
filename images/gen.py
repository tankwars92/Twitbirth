from PIL import Image, ImageDraw, ImageFont

def generate_twitter2006_logo(text="twitter", save_path="twitter2006_logo.png"):
    width, height = 210, 49
    gradient_bottom = (100, 215, 245)
    gradient_top = (176, 242, 254)
    outline_color = (255, 255, 255, 255)
    outline_thickness = 3  # пухлая обводка
    bottom_padding = 2
    top_padding = 6

    # Шрифт
    font_path = "PICOBLA_.TTF"
    font_size = 100
    font = ImageFont.truetype(font_path, font_size)

    # Получаем размеры текста
    bbox = font.getbbox(text)
    text_width = bbox[2] - bbox[0]
    text_height = bbox[3] - bbox[1]

    # Масштабируем по ширине, чтобы текст поместился с учетом обводки
    scale = (width - outline_thickness*2 - 2) / text_width
    font_size = int(font_size * scale)
    font = ImageFont.truetype(font_path, font_size)
    bbox = font.getbbox(text)
    text_width = bbox[2] - bbox[0]
    text_height = bbox[3] - bbox[1]

    # Позиция текста с учетом обводки
    x = (width - text_width) // 2
    y = height - text_height - bottom_padding - top_padding

    # Маска текста
    mask = Image.new("L", (text_width, text_height), 0)
    mask_draw = ImageDraw.Draw(mask)
    mask_draw.text((-bbox[0], -bbox[1]), text, font=font, fill=255)

    # Обводка через маску
    outline_img = Image.new("RGBA", (width, height), (0,0,0,0))
    draw_outline = ImageDraw.Draw(outline_img)
    for dx in range(-outline_thickness, outline_thickness+1):
        for dy in range(-outline_thickness, outline_thickness+1):
            draw_outline.bitmap((x+dx, y+dy), mask, fill=outline_color)

    # Градиент текста
    gradient = Image.new("RGBA", (text_width, text_height), (0,0,0,0))
    grad_draw = ImageDraw.Draw(gradient)
    for i in range(text_height):
        t = 1 - i / text_height
        r = int(gradient_bottom[0]*(1-t) + gradient_top[0]*t)
        g = int(gradient_bottom[1]*(1-t) + gradient_top[1]*t)
        b = int(gradient_bottom[2]*(1-t) + gradient_top[2]*t)
        grad_draw.line([(0,i),(text_width,i)], fill=(r,g,b,255))

    # Вставляем градиент через маску поверх обводки
    outline_img.paste(gradient, (x, y), mask)

    # Сохраняем
    outline_img.save(save_path)
    print(f"Логотип сохранён как {save_path}, размер: {width}x{height}")

# Генерация
generate_twitter2006_logo("twitbirth")