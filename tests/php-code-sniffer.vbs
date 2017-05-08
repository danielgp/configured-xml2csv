Set WshShell   = WScript.CreateObject("WScript.Shell")
CheckedPHPversions = Array("5.6", "7.0", "7.1")
CheckStandards = Array("PSR1", "PSR2")

MsgBox "I will assess the PHP projects for compatibility with predefined standards!"

strCurDir      = WshShell.CurrentDirectory
strBaseDir     = Replace(strCurDir, "tests", "")

For Each currentPHPversion In CheckedPHPversions
    WshShell.Run "C:\www\App\PHP\7.1.x64\php.exe -c C:\www\Config\PHP\7.1.x64\php.ini " & strBaseDir & "vendor\squizlabs\php_codesniffer\scripts\phpcs -p -v --extensions=php -d date.timezone=""Europe/Bucharest"" --encoding=utf-8 --report=xml --standard=PHPCompatibility --runtime-set testVersion " & currentPHPversion & " " & strBaseDir & " --report-file=" & strCurDir & "\php-code-sniffer\php_" & currentPHPversion & ".xml --ignore=*/data/*,*/tests/*,*/tmp/*,*/vendor/*", 0, True
Next

For Each crtStandard In CheckStandards
    WshShell.Run "C:\www\App\PHP\7.1.x64\php.exe -c C:\www\Config\PHP\7.1.x64\php.ini " & strBaseDir & "vendor\squizlabs\php_codesniffer\scripts\phpcs -p -v --extensions=php -d date.timezone=""Europe/Bucharest"" --encoding=utf-8 --report=xml --standard=" & crtStandard & " " & strBaseDir & " --report-file=" & strCurDir & "\php-code-sniffer\" & crtStandard & ".xml --ignore=*/data/*,*/tests/*,*/tmp/*,*/vendor/*", 0, True
Next

MsgBox "I finished generating XML files with PHP-Code-Sniffer results!"
