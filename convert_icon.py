from PIL import Image
import os

def convert_png_to_ico(png_path="icon.png", ico_path="icon.ico"):
    """
    Converte o icon.png para icon.ico na mesma pasta.
    """
    if not os.path.exists(png_path):
        print(f"Erro: Arquivo '{png_path}' nao encontrado. Verifique se ele esta na pasta.")
        return

    try:
        img = Image.open(png_path)
        # Define varios tamanhos para garantir boa aparencia em diferentes contextos
        sizes = [(256, 256), (128, 128), (64, 64), (48, 48), (32, 32), (16, 16)]
        img.save(ico_path, format='ICO', sizes=sizes)
        print(f"Sucesso! Arquivo '{ico_path}' criado.")
    except Exception as e:
        print(f"Ocorreu um erro durante a conversao: {e}")
        print("Por favor, certifique-se de que a biblioteca Pillow esta instalada: pip install Pillow")

if __name__ == "__main__":
    convert_png_to_ico()
