<?
$MESS["DAV_HELP_NAME"] = "Модуль DAV";
$MESS["DAV_HELP_TEXT"] = "Натисніть на кнопку додавання нового облікового запису,
підтримують протоколи CalDAV і CardDAV. Ці протоколи підтримують, наприклад, мобільні пристрої iPhone і iPad.
Також підтримка протоколів присутня в програмах Mozilla Sunbird, eM Client та деяких інших.<br><br>
<ul>
 <li><b><a href=\"#caldav\">Підключення за протоколом CalDav</a></b>
 <ul>
  <li><a href=\"#caldavipad\"> Підключення iPhone</a></li>
  <li><a href=\"#carddavsunbird\"> Підключення Mozilla Sunbird</a></li>
 </ul>
 </li>
 <li><b><a href=\"#carddav\"> Підключення за протоколом CardDav</a></b></li>
</ul>

<br><br>

<h3><a name=\"caldav\"></a> Підключення за протоколом CalDav</h3>

<h4><a name=\"caldavipad\"></a> Підключення iPhone</h4>

Для того, щоб налаштувати підтримку CalDAV у пристроях Apple, виконайте наступні дії:
<ol>
<li> На пристрої Apple, зайдіть у <b>Налаштування</b> та перейдіть на вкладку <b>Облікові записи</b>.</li>
<li> Натисніть на кнопку додавання нового облікового запису.</li>
<li> Виберіть тип облікового запису CalDAV.</li>
<li> У налаштуваннях параметрів облікового запису встановіть адресу цього сайту (#SERVER#) в якості сервера, а також ваші логін і пароль.</li>
<li> В якості типу авторизації слід використовувати базову авторизацію (Basic Authorization).</li>
<li> При необхідності після збереження запису можна зайти в його редагування і задати порт сайту.</li>
</ol>

Ваші календарі автоматично з'являться у застосунку «Календар».<br>
При необхідності підключення календарів інших користувачів або календарів груп необхідно відповідно задати посилання виду:<br>
<i>#SERVER#/bitrix/groupdav.php/код сайту/ім’я користувача/calendar/</i><br>
та<br>
<i>#SERVER#/bitrix/groupdav.php/код сайту/group-код групи/calendar/</i><br>

<br><br>

<h4><a name=\"carddavsunbird\"></a>Підключення Mozilla Sunbird</h4>

Щоб налаштувати підтримку CalDAV у Mozilla Sunbird, виконайте наступні дії:
<ol>
<li>Запустіть застосунок Sunbird та виберіть <b>Файл &gt; Новий календар</b>.</li>
<li>Виберіть пункт <b>У мережі</b> (<b>On the Network</b>) та натисніть кнопку <b>Далі</b>.</li>
<li> Виберіть формат <b>CalDAV</b>.</li>
<li>У полі <b>Розташування</b> (<b>Location</b>) введіть<br>
<i>#SERVER#/bitrix/groupdav.php/код сайту/ім’я користувача/calendar/код календаря/</i><br>
або<br>
<i>#SERVER#/bitrix/groupdav.php/код сайту/group-код групи/calendar/код календаря/</i><br>
та натисніть кнопку <b>Далі</b>.</li>
<li> Задайте назву та виберіть колір для вашого календаря.</li>
<li> У спливаючому вікні введіть ваше ім'я користувача та пароль.</li>
</ol>

<br><br>

<h3><a name=\"carddav\"></a>Підключення за протоколом CardDav</h3>

Для того, щоб налаштувати підтримку CardDAV в пристроях Apple, виконайте наступні дії:
<ol>
<li>На пристрої Apple зайдіть у <b>Налаштування</b> й перейдіть на вкладку <b>Облікові записи </b>.</li>
<li>Натисніть на кнопку додавання нового облікового запису.</li>
<li>Виберіть тип облікового запису CardDAV.</li>
<li>У налаштуваннях параметрів облікового запису встановіть адресу цього сайту (#SERVER#) в якості сервера, а так само ваші логін і пароль.</li>
<li>В якості типу авторизації слід використовувати базову авторизацію (Basic Authorization).</li>
<li>При необхідності після збереження запису можна зайти у його редагування й задати порт сайту.</li>
</ol>

Ваші контакти автоматично з'являться у застосунку «Контакти».<br>";
?>