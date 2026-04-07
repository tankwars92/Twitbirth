from PIL import Image, ImageDraw, ImageFont

def generate_animated_button(text="twitbirth", save_path="twitbirth_button.gif", frames_count=8):
    width, height = 88, 31
    gradient_bottom = (100, 215, 245)
    gradient_top = (176, 242, 254)
    outline_color = (255, 255, 255, 255)
    outline_thickness = 2
    side_padding = 4
    top_padding = 2

    font_path = "PICOBLA_.TTF"
    font_size = 50

    # Подбираем шрифт
    font = ImageFont.truetype(font_path, font_size)
    bbox = font.getbbox(text)
    text_width = bbox[2] - bbox[0]
    text_height = bbox[3] - bbox[1]

    scale = min((width - side_padding*2 - outline_thickness*2) / text_width,
                (height - top_padding*2 - outline_thickness*2) / text_height)
    font_size = int(font_size * scale)
    font = ImageFont.truetype(font_path, font_size)
    bbox = font.getbbox(text)
    text_width = bbox[2] - bbox[0]
    text_height = bbox[3] - bbox[1]

    x = (width - text_width) // 2
    y = (height - text_height) // 2

    frames = []

    import random

    for f in range(frames_count):
        # Создаем фон с пульсацией
        factor = 0.9 + 0.1 * (f / frames_count)
        bg_color_top = tuple(int(c*factor) for c in gradient_top)
        bg_color_bottom = tuple(int(c*factor) for c in gradient_bottom)
        bg = Image.new("RGBA", (width, height), (0,0,0,0))
        draw_bg = ImageDraw.Draw(bg)
        for i in range(height):
            t = i / height
            r = int(bg_color_top[0]*(1-t) + bg_color_bottom[0]*t)
            g = int(bg_color_top[1]*(1-t) + bg_color_bottom[1]*t)
            b = int(bg_color_top[2]*(1-t) + bg_color_bottom[2]*t)
            draw_bg.line([(0,i),(width,i)], fill=(r,g,b,255))

        # Маска текста
        mask = Image.new("L", (text_width, text_height), 0)
        mask_draw = ImageDraw.Draw(mask)
        mask_draw.text((-bbox[0], -bbox[1]), text, font=font, fill=255)

        # Обводка
        outline_img = bg.copy()
        draw_outline = ImageDraw.Draw(outline_img)
        for dx in range(-outline_thickness, outline_thickness+1):
            for dy in range(-outline_thickness, outline_thickness+1):
                offset_x = x + dx + random.randint(-1,1)  # дрожание
                offset_y = y + dy + random.randint(-1,1)
                draw_outline.bitmap((offset_x, offset_y), mask, fill=outline_color)

        # Градиент текста
        gradient = Image.new("RGBA", (text_width, text_height), (0,0,0,0))
        grad_draw = ImageDraw.Draw(gradient)
        for i in range(text_height):
            t = 1 - i / text_height
            r = int(gradient_bottom[0]*(1-t) + gradient_top[0]*t)
            g = int(gradient_bottom[1]*(1-t) + gradient_top[1]*t)
            b = int(gradient_bottom[2]*(1-t) + gradient_top[2]*t)
            grad_draw.line([(0,i),(text_width,i)], fill=(r,g,b,255))

        # Вставляем градиент текста через маску поверх обводки
        outline_img.paste(gradient, (x + random.randint(-1,1), y + random.randint(-1,1)), mask)

        # Тонкая рамка
        draw_final = ImageDraw.Draw(outline_img)
        draw_final.rectangle([0,0,width-1,height-1], outline=(150,150,150,255))

        frames.append(outline_img)

    # Сохраняем анимированный GIF
    frames[0].save(save_path, save_all=True, append_images=frames[1:], optimize=False, duration=100, loop=0)
    print(f"Анимированная кнопка сохранена как {save_path}")

# Генерация
generate_animated_button("twitbirth")