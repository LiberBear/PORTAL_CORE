<?
$MESS["SECURITY_SITE_CHECKER_EnvironmentTest_NAME"] = "Перевірка налаштувань оточення";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION"] = "Імовірно в директорії зберігання сесій знаходяться сесії різних проектів";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_DETAIL"] = "Залежно від ситуації це може призвести до повної компрометації ресурсу";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_RECOMMENDATION"] = "Використовувати окреме сховище для кожного проекту";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP"] = "PHP скрипти виконуються в директорії зберігання завантажуваних файлів";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DETAIL"] = "Розробники іноді забувають про правильну фільтрацію імен файлів, якщо це трапиться зловмисник зможе повністю скомпрометувати ресурс";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_RECOMMENDATION"] = "Коректно налаштувати веб-сервер";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE"] = "PHP скрипти з подвійним розширенням (eg php.lala) виконуються на директорії зберігання завантажуваних файлів";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE_DETAIL"] = "Розробники іноді забувають про правильну фільтрацію імен файлів, якщо це трапиться зловмисник зможе повністю скомпрометувати ресурс";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE_RECOMMENDATION"] = "Коректно налаштувати веб-сервер";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY"] = "Py скрипти виконуються в директорії зберігання завантажуваних файлів";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY_DETAIL"] = "Розробники іноді забувають про правильну фільтрацію імен файлів, якщо це трапиться зловмисник зможе повністю скомпрометувати ресурс";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY_RECOMMENDATION"] = "Коректно налаштувати веб-сервер";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS"] = ". htaccess файли не повинні оброблятися Apache в директорії зберігання завантажуваних файлів";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS_DETAIL"] = "Розробники іноді забувають про правильну фільтрацію імен файлів, якщо це трапиться зловмисник зможе повністю скомпрометувати ресурс";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS_RECOMMENDATION"] = "Коректно налаштувати веб-сервер";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION"] = "Apache Content Negotiation дозволений в директорії зберігання завантажуваних файлів";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION_DETAIL"] = "Apache Content Negotiation не рекомендований для використання, тому що може служити джерелом XSS нападу";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION_RECOMMENDATION"] = "Коректно налаштувати веб-сервер";
$MESS["SECURITY_SITE_CHECKER_SESSION_DIR"] = "Директорія зберігання файлів сесій доступна для всіх системних користувачів";
$MESS["SECURITY_SITE_CHECKER_SESSION_DIR_DETAIL"] = "Це може дозволити читати / змінювати сесійні дані, через скрипти інших віртуальних серверів";
$MESS["SECURITY_SITE_CHECKER_SESSION_DIR_RECOMMENDATION"] = "Коректно налаштувати файлові права або змінити директорію зберігання або включити зберігання сесій в БД: <a href=\"/bitrix/admin/security_session.php\"> Захист сесій </ a>";
$MESS["SECURITY_SITE_CHECKER_SESSION_DIR_ADDITIONAL"] = "Директорія зберігання сесій: #DIR#<br>
Права: #PERMS#
";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_ADDITIONAL_OWNER"] = "Причина: власник файлу відрізняється від поточного користувача <br>
Файл: #FILE# <br>
UID власника файлу: #FILE_ONWER# <br>
UID поточного користувача: #CURRENT_OWNER# <br>
";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_ADDITIONAL_SIGN"] = "Причина: файл сесії не містить підпису поточного сайту <br>
Файл: #FILE#<br>
Підпис поточного сайту: #SIGN#<br>
Вміст файлу: <pre> #FILE_CONTENT# </pre>
";
?>