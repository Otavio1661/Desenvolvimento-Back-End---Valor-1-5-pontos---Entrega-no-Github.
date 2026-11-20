@echo off
rem Sobe o MongoDB local (porta 27017) usando o pacote baixado em %TEMP%\mongodl\x
rem Deixe esta janela aberta enquanto usa o sistema.
setlocal
set "MONGOD="
for /d %%D in ("%TEMP%\mongodl\x\*") do if exist "%%D\bin\mongod.exe" set "MONGOD=%%D\bin\mongod.exe"
if "%MONGOD%"=="" (
  echo mongod.exe nao encontrado. Instale o MongoDB Community ou ajuste este arquivo.
  pause
  exit /b 1
)
if not exist "%USERPROFILE%\mongodata" mkdir "%USERPROFILE%\mongodata"
"%MONGOD%" --dbpath "%USERPROFILE%\mongodata" --bind_ip 127.0.0.1 --port 27017
