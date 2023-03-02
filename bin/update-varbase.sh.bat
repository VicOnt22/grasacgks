@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../vendor/vardot/varbase-updater/scripts/update/update-varbase.sh
SET COMPOSER_RUNTIME_BIN_DIR=%~dp0
bash "%BIN_TARGET%" %*
