GW-analytics
===================
Всем привет. Как и обещал выкладываю свои наработки по парсингу GW и обработке информации
Сразу прошу не пинать за грязный код :)

Пример тут: [http://6496.ynblpb.com/](http://6496.ynblpb.com/)

> **DOTO (известные косяки):**
> - упростить работу с БД (файл mysql.class.php не такой уж и нужный)

----------
Необходимый софт
-------------
 1. php5
 2. caspersJS (+phantomJS)
 3. mysql

Как пользоваться
-------------
 1. Надо отредактировать номер синдиката в файлах syndycate.*.js
 2. Отредактировать config.php и прописать необходимые пути, логины и пароли
 3. Запихать в mysql структуру базы (SQL ниже)
 4. Запустить в консоли php-cgi syndicate.log.php и проверить правильность работы системы
 5. Повесить в крон запуск скриптов (время как вам удобнее выставляйте):
	 **syndicate.pts.php** - раз в 30 минут
	 **syndicate.log.php** - раз в 5 минут
	 **syndicate.members.php** - раз в сутки
 6. Создать в веб-сервере новый vhost (если требуется и рабочую директорию сделать докрутом). в index.php будет вся аналитика с графиками, log.php собирает всю информацию по конкретному пользователю
 7. По желанию настроить автоматическую чистку БД (например, за последние пару недель только оставлять данные)


SQL Dump
-------------
```
--
-- Table structure for table `syndicate_log`
--

CREATE TABLE IF NOT EXISTS `syndicate_log` (
  `id` int(10) NOT NULL,
  `cdate` datetime NOT NULL,
  `event` text NOT NULL,
  `md5` varchar(32) NOT NULL,
  `type` varchar(250) NOT NULL,
  `who` bigint(20) NOT NULL,
  `plus_gb` int(10) NOT NULL,
  `minus_gb` int(10) NOT NULL,
  `plus_pts` int(10) NOT NULL,
  `minus_pts` int(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `syndicate_members`
--

CREATE TABLE IF NOT EXISTS `syndicate_members` (
  `id` int(10) NOT NULL,
  `name` varchar(266) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `syndicate_log`
--
ALTER TABLE `syndicate_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cdate` (`cdate`,`md5`);

--
-- Indexes for table `syndicate_members`
--
ALTER TABLE `syndicate_members`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `syndicate_log`
--
ALTER TABLE `syndicate_log`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `syndicate_members`
--
ALTER TABLE `syndicate_members`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

```



