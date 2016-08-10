# codeigniter-wcal
This is a rewrite of the CodeIgniter Calendar library to show a 7day calendar based on ISO 8601 week. 

I tried to keep it as close to the Calendar library as possible but there are some changes because the nature of the Weekly calendar.

ISO 8601 weeks always start on Monday, this calendar only shows 7 days (Monday to Sunday), there are no previous days to show. 

I've changed the labels to show the week number and the year, and remove as much of the month specific information as possible. 

Because ISO weeks can split months, I've changed the day labels to show the month and day number. Also I changed how you pass data: 

```PHP
$this->load->library('wcal');

$data = array(
        '2016-08-08'  => 'http://example.com/news/article/2016/08/08/',
        '2006-08-10'  => 'http://example.com/news/article/2016/08/10/',
        '2006-08-12' => 'http://example.com/news/article/2016/08/12/',
        '2006-08-14' => 'http://example.com/news/article/2016/08/14/'
);

echo $this->wcal->generate(2016, 32, $data);

```

This eliminates just using the day number, and uses the ISO date as the index, which should eliminate having to transform dates with DAYOFMONTH or DATEPART 
depending on which database you use. 

It uses the same templating as the CI Calendar class.

To install add to application/libraries and call:
```PHP
$this->load->library('wcal');
```
To generate
```PHP
$this->wcal->generate(); //this will show the current ISO week. 

$this->wcal->generate(2016, 32, $data); //this will show ISO week 32 of 2016 
```
