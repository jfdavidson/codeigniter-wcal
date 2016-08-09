<?php
/**
 * @package	Wcal
 * @author	James Davidson
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://github.com/jfdavidson/codeigniter-wcal
 * @since	Version 0.0.1
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Wcal Class
 *
 * This class enables the creation of a 7 day calendar for ISO week. This is just a hack of the fine work of the Ellis Lab Team's
 * Calendar library. You should probably just extend the calendar library to accept a week type calendar. 
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		James Davidson
 * @link		https://github.com/jfdavidson/codeigniter-wcal
 * @version 	0.0.1 
 */
class Wcal {

	/**
	 * Calendar layout template
	 *
	 * @var mixed
	 */
	public $template = '';

	/**
	 * Replacements array for template
	 *
	 * @var array
	 */
	public $replacements = array();

	
	/**
	 * How to display names of days
	 *
	 * @var string
	 */
	public $day_type = 'abr';

	/**
	 * Whether to show next/prev week links
	 *
	 * @var bool
	 */
	public $show_next_prev = FALSE;

	/**
	 * Url base to use for next/prev week links
	 *
	 * @var bool
	 */
	public $next_prev_url = '';

	

	// --------------------------------------------------------------------

	/**
	 * CI Singleton
	 *
	 * @var object
	 */
	protected $CI;

	// --------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * Loads the calendar language file and sets the default time reference.
	 *
	 * @uses	CI_Lang::$is_loaded
	 *
	 * @param	array	$config	Calendar options
	 * @return	void
	 */
	public function __construct($config = array())
	{
		$this->CI =& get_instance();
		$this->CI->lang->load('calendar');

		empty($config) OR $this->initialize($config);

		log_message('info', 'Wcal Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize the user preferences
	 *
	 * Accepts an associative array as input, containing display preferences
	 *
	 * @param	array	config preferences
	 * @return	CI_Wcal
	 */
	public function initialize($config = array())
	{
		foreach ($config as $key => $val)
		{
			if (isset($this->$key))
			{
				$this->$key = $val;
			}
		}

		// Set the next_prev_url to the controller if required but not defined
		if ($this->show_next_prev === TRUE && empty($this->next_prev_url))
		{
			$this->next_prev_url = $this->CI->config->site_url($this->CI->router->class.'/'.$this->CI->router->method);
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Generate the calendar
	 *
	 * @param	int	the year
	 * @param	int	the ISO week
	 * @param	array	the data to be shown in the calendar cells
	 * @return	string
	 */
	public function generate($year = '', $week = '', $data = array())
	{
		$local_time = time();

		// Set and validate the supplied week/year
		if (empty($year))
		{
			$year = date('Y', $local_time);
		}
		elseif (strlen($year) === 1)
		{
			$year = '200'.$year;
		}
		elseif (strlen($year) === 2)
		{
			$year = '20'.$year;
		}

		if (empty($week))
		{
			$week = date('W', $local_time);
		}
		elseif (strlen($week) === 1)
		{
			$week = '0'.$week;
		}
		
		$adjusted_date = $this->adjust_date($week, $year);

		$week	= $adjusted_date['week'];
		$year	= $adjusted_date['year'];

		// Set the starting day of the week, ISO Week always starts on Monday
		$start_days	= array('sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6);
		$start_day	= 1;
		
		// Set the starting day number
		
		$day  = $start_day;
		
		// Set the current week/year/day
		// We use this to determine the "today" date
		$cur_year	= date('Y', $local_time);
		$cur_week	= date('W', $local_time);
		$cur_day	= date('j', $local_time);

		$is_current_week = ($cur_year == $year && $cur_week == $week);

		// Generate the template data array
		$this->parse_template();

		// Begin building the calendar output
		$out = $this->replacements['table_open']."\n\n".$this->replacements['heading_row_start']."\n";

		// "previous" week link
		if ($this->show_next_prev === TRUE)
		{
			// Add a trailing slash to the URL if needed
			$this->next_prev_url = preg_replace('/(.+?)\/*$/', '\\1/', $this->next_prev_url);

			$adjusted_date = $this->adjust_date($week - 1, $year);
			$out .= str_replace('{previous_url}', $this->next_prev_url.$adjusted_date['year'].'/'.$adjusted_date['week'], $this->replacements['heading_previous_cell'])."\n";
		}

		// Heading containing the ISO week/year
		$colspan = ($this->show_next_prev === TRUE) ? 5 : 7;

		$this->replacements['heading_title_cell'] = str_replace('{colspan}', $colspan,
								str_replace('{heading}', 'Week&nbsp;'.$week.'&nbsp;'.$year, $this->replacements['heading_title_cell']));

		$out .= $this->replacements['heading_title_cell']."\n";

		// "next" week link
		if ($this->show_next_prev === TRUE)
		{
			$adjusted_date = $this->adjust_date($week + 1, $year);
			$out .= str_replace('{next_url}', $this->next_prev_url.$adjusted_date['year'].'/'.$adjusted_date['week'], $this->replacements['heading_next_cell']);
		}

		$out .= "\n".$this->replacements['heading_row_end']."\n\n"
			// Write the cells containing the days of the week
			.$this->replacements['week_row_start']."\n";

		$day_names = $this->get_day_names();

		for ($i = 0; $i < 7; $i ++)
		{
			$out .= str_replace('{week_day}', $day_names[($start_day + $i) %7], $this->replacements['week_day_cell']);
		}

		$out .= "\n".$this->replacements['week_row_end']."\n";

		// Build the main body of the calendar
		while ($day <= 7)
		{
			$out .= "\n".$this->replacements['cal_row_start']."\n";

			for ($i = 0; $i < 7; $i++)
			{
				//since ISO weeks span months we give them meaningfull labels.
				$label = date('M d',strtotime("{$year}W{$week} + {$i} days"));
				
				//Use the ISO 8601 date to fetch the data, this is probably easier than using the day number like the calendar library. 
				$isodate = date('Y-m-d',strtotime("{$year}W{$week} + {$i} days"));
				
				if ($day > 0 && $day <= 7)
				{
					$out .= ($is_current_week === TRUE && $day == $cur_day) ? $this->replacements['cal_cell_start_today'] : $this->replacements['cal_cell_start'];

					if (isset($data[$day]))
					{
						// Cells with content
						$temp = ($is_current_week === TRUE && $day == $cur_day) ?
								$this->replacements['cal_cell_content_today'] : $this->replacements['cal_cell_content'];
						$out .= str_replace(array('{content}', '{day}'), array($data[$isodate], $label), $temp);
					}
					else
					{
						// Cells with no content
						$temp = ($is_current_week === TRUE && $day == $cur_day) ?
								$this->replacements['cal_cell_no_content_today'] : $this->replacements['cal_cell_no_content'];
						$out .= str_replace('{day}', $label, $temp);
					}

					$out .= ($is_current_week === TRUE && $day == $cur_day) ? $this->replacements['cal_cell_end_today'] : $this->replacements['cal_cell_end'];
				}
				else
				{
					// Blank cells
					$out .= $this->replacements['cal_cell_start'].$this->replacements['cal_cell_blank'].$this->replacements['cal_cell_end'];
				}

				$day++;
			}

			$out .= "\n".$this->replacements['cal_row_end']."\n";
		}

		return $out .= "\n".$this->replacements['table_close'];
	}

	// --------------------------------------------------------------------

	/**
	 * Get Day Names
	 *
	 * Returns an array of day names (Sunday, Monday, etc.) based
	 * on the type. Options: long, short, abr
	 *
	 * @param	string
	 * @return	array
	 */
	public function get_day_names($day_type = '')
	{
		if ($day_type !== '')
		{
			$this->day_type = $day_type;
		}

		if ($this->day_type === 'long')
		{
			$day_names = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
		}
		elseif ($this->day_type === 'short')
		{
			$day_names = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
		}
		else
		{
			$day_names = array('su', 'mo', 'tu', 'we', 'th', 'fr', 'sa');
		}

		$days = array();
		for ($i = 0, $c = count($day_names); $i < $c; $i++)
		{
			$days[] = ($this->CI->lang->line('cal_'.$day_names[$i]) === FALSE) ? ucfirst($day_names[$i]) : $this->CI->lang->line('cal_'.$day_names[$i]);
		}

		return $days;
	}

	// --------------------------------------------------------------------

	/**
	 * Adjust Date
	 *
	 * This function makes sure that we have a valid ISO week/year.
	 *
	 * @param	int	the ISO week
	 * @param	int	the year
	 * @return	array
	 */
	public function adjust_date($week, $year)
	{
		$date = array();

		$date['week']	= $week;
		$date['year']	= $year;
		
		$num_weeks = $this->get_total_weeks($year);

		while ($date['week'] > $num_weeks)
		{
			$date['week'] -= $num_weeks;
			$date['year']++;
		}

		while ($date['week'] <= 1)
		{
			$date['year']--;
			$num_weeks = $this->get_total_weeks($date['$year']);
			$date['week'] += $num_weeks;
			
		}

		if (strlen($date['week']) === 1)
		{
			$date['week'] = '0'.$date['week'];
		}

		return $date;
	}

	// --------------------------------------------------------------------

	/**
	 * Total ISO Weeks in a given year.
	 * ISO years can have either 52 or 53 weeks depending on how days lay out. 
	 *
	 * 
	 * @param	int	the year
	 * @return	int
	 */
	public function get_total_weeks($year)
	{
		$num_weeks = date('W', mktime(0,0,0,12,28,$year) ); 
		return $num_weeks;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Default Template Data
	 *
	 * This is used in the event that the user has not created their own template
	 *
	 * @return	array
	 */
	public function default_template()
	{
		return array(
			'table_open'				=> '<table border="0" cellpadding="4" cellspacing="0">',
			'heading_row_start'			=> '<tr>',
			'heading_previous_cell'		=> '<th><a href="{previous_url}">&lt;&lt;</a></th>',
			'heading_title_cell'		=> '<th colspan="{colspan}">{heading}</th>',
			'heading_next_cell'			=> '<th><a href="{next_url}">&gt;&gt;</a></th>',
			'heading_row_end'			=> '</tr>',
			'week_row_start'			=> '<tr>',
			'week_day_cell'				=> '<td>{week_day}</td>',
			'week_row_end'				=> '</tr>',
			'cal_row_start'				=> '<tr>',
			'cal_cell_start'			=> '<td>',
			'cal_cell_start_today'		=> '<td>',
			'cal_cell_start_other'		=> '<td style="color: #666;">',
			'cal_cell_content'			=> '<a href="{content}">{day}</a>',
			'cal_cell_content_today'	=> '<a href="{content}"><strong>{day}</strong></a>',
			'cal_cell_no_content'		=> '{day}',
			'cal_cell_no_content_today'	=> '<strong>{day}</strong>',
			'cal_cell_blank'			=> '&nbsp;',
			'cal_cell_other'			=> '{day}',
			'cal_cell_end'				=> '</td>',
			'cal_cell_end_today'		=> '</td>',
			'cal_cell_end_other'		=> '</td>',
			'cal_row_end'				=> '</tr>',
			'table_close'				=> '</table>'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse Template
	 *
	 * Harvests the data within the template {pseudo-variables}
	 * used to display the calendar
	 *
	 * @return	CI_Calendar
	 */
	public function parse_template()
	{
		$this->replacements = $this->default_template();

		if (empty($this->template))
		{
			return $this;
		}

		if (is_string($this->template))
		{
			$today = array('cal_cell_start_today', 'cal_cell_content_today', 'cal_cell_no_content_today', 'cal_cell_end_today');

			foreach (array('table_open', 'table_close', 'heading_row_start', 'heading_previous_cell', 'heading_title_cell', 'heading_next_cell', 'heading_row_end', 'week_row_start', 'week_day_cell', 'week_row_end', 'cal_row_start', 'cal_cell_start', 'cal_cell_content', 'cal_cell_no_content', 'cal_cell_blank', 'cal_cell_end', 'cal_row_end', 'cal_cell_start_today', 'cal_cell_content_today', 'cal_cell_no_content_today', 'cal_cell_end_today', 'cal_cell_start_other', 'cal_cell_other', 'cal_cell_end_other') as $val)
			{
				if (preg_match('/\{'.$val.'\}(.*?)\{\/'.$val.'\}/si', $this->template, $match))
				{
					$this->replacements[$val] = $match[1];
				}
				elseif (in_array($val, $today, TRUE))
				{
					$this->replacements[$val] = $this->replacements[substr($val, 0, -6)];
				}
			}
		}
		elseif (is_array($this->template))
		{
			$this->replacements = array_merge($this->replacements, $this->template);
		}

		return $this;
	}

}
