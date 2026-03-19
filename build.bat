@echo off
title Compilador do Omni Downloader

REM --- Verificacao de Dependencias ---
echo Verificando se a biblioteca Pillow esta instalada...
pip show Pillow > nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo AVISO: A biblioteca 'Pillow' nao esta instalada.
    echo Ela e necessaria para converter o icone.
    echo Por favor, execute o comando: pip install Pillow
    echo.
    pause
    exit /b
)

REM --- Conversao do Icone ---
echo.
echo Convertendo icone.png para icon.ico...
python convert_icon.py

REM Verifica se o icone foi criado
if not exist icon.ico (
    echo.
    echo ERRO: Nao foi possivel criar o arquivo icon.ico.
    echo Verifique a saida do script de conversao acima.
    echo.
    pause
    exit /b
)
echo Icone convertido com sucesso.

REM --- Compilacao com PyInstaller ---
echo.
echo Executando o PyInstaller...
rem Usando o comando completo com hidden-imports para mais seguranca
pyinstaller --name "OmniDownloader" --onefile --windowed --icon="icon.ico" --hidden-import="mutagen.id3" --hidden-import="mutagen.easyid3" --hidden-import="mutagen.mp3" omni_downloader.py

echo.
echo ----------------------------------------------------------------
if exist "dist\OmniDownloader.exe" (
    echo PROCESSO CONCLUIDO COM SUCESSO!
    echo O seu executavel esta na pasta 'dist'.
) else (
    echo OCORREU UM ERRO DURANTE A COMPILACAO.
    echo Verifique as mensagens de erro do PyInstaller acima.
)
echo ----------------------------------------------------------------
echo.
pause
