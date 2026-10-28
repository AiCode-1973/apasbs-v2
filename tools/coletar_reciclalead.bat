@echo off
REM ======================================================================
REM  APASBS - coleta semanal do ReciclaLead
REM
REM  Roda o coletor, guarda o que aconteceu em logs\reciclalead-AAAA-MM.log
REM  e devolve o codigo de saida para o Agendador de Tarefas do Windows
REM  (0 = deu certo, 1 = falhou).
REM
REM  Antes de agendar, ajuste as duas linhas abaixo e teste rodando este
REM  .bat com dois cliques. Se a janela fechar sem erro e a competencia
REM  aparecer em Central de Agendamento > Coletas, esta pronto.
REM ======================================================================

REM --- Ajuste 1: onde esta o php.exe -------------------------------------
set PHP=C:\xampp1\php\php.exe

REM --- Ajuste 2: onde esta o sistema -------------------------------------
set APASBS=C:\xampp1\htdocs\apasbs-v2

REM ----------------------------------------------------------------------

if not exist "%PHP%" (
    echo [ERRO] Nao achei o PHP em %PHP%
    echo Corrija a linha "set PHP=" no comeco deste arquivo.
    exit /b 1
)

if not exist "%APASBS%\tools\coletar_reciclalead.php" (
    echo [ERRO] Nao achei o sistema em %APASBS%
    echo Corrija a linha "set APASBS=" no comeco deste arquivo.
    exit /b 1
)

if not exist "%APASBS%\config\reciclalead.php" (
    echo [ERRO] Falta o arquivo config\reciclalead.php com o usuario do robo.
    echo Copie o config\reciclalead.example.php, renomeie e preencha.
    exit /b 1
)

if not exist "%APASBS%\logs" mkdir "%APASBS%\logs"

for /f "tokens=1-2 delims=/" %%a in ("%date:~3,10%") do set MESANO=%%b-%%a
set LOG=%APASBS%\logs\reciclalead-%MESANO%.log

echo. >> "%LOG%"
echo ===== %date% %time% ===== >> "%LOG%"

pushd "%APASBS%"
"%PHP%" tools\coletar_reciclalead.php --verboso >> "%LOG%" 2>&1
set CODIGO=%ERRORLEVEL%
popd

if %CODIGO% NEQ 0 (
    echo [FALHOU] veja o fim de %LOG% >> "%LOG%"
    echo [FALHOU] A coleta nao terminou. Veja %LOG%
) else (
    echo [OK] coleta concluida >> "%LOG%"
    echo [OK] Coleta concluida. Confira em Central de Agendamento ^> Coletas.
)

exit /b %CODIGO%
