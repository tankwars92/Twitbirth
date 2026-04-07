from PIL import Image

input_file = "twitter2006_logo.png"
output_file = "twitter.png"

img = Image.open(input_file).convert("RGBA")

r, g, b, a = img.split()
rgb = Image.merge("RGB", (r, g, b))

# Конвертация в палитру
pal = rgb.convert("P", palette=Image.ADAPTIVE, colors=255)

# Получаем палитру
palette = pal.getpalette()

# Добавляем новый цвет (например, ярко-розовый, которого точно нет)
transparent_color = (255, 0, 255)

palette.extend(transparent_color)  # добавляем в конец
pal.putpalette(palette)

transparent_index = len(palette) // 3 - 1  # индекс нового цвета

# Маска прозрачности
mask = a.point(lambda x: 255 if x < 128 else 0)

# Применяем прозрачность
pal.paste(transparent_index, mask)

# Указываем прозрачный индекс
pal.info["transparency"] = transparent_index

pal.save(output_file, "PNG")

print("Готово без потери белого:", output_file)