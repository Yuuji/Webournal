<h2>{translate name="WEBOURNAL_CALENDAR_TITLE"}Calendar{/translate}</h2>
<div class="calendarnavigation">
	<form action="{$Core->url('index', 'calendar', 'webournal')}" method="GET">
	<select name="month">
		<option value="1"{if $calendar_month==1} selected{/if}>{translate name="JANUARY"}January{/translate}</option>
		<option value="2"{if $calendar_month==2} selected{/if}>{translate name="FEBRUARY"}February{/translate}</option>
		<option value="3"{if $calendar_month==3} selected{/if}>{translate name="MARCH"}March{/translate}</option>
		<option value="4"{if $calendar_month==4} selected{/if}>{translate name="APRIL"}April{/translate}</option>
		<option value="5"{if $calendar_month==5} selected{/if}>{translate name="MAY"}May{/translate}</option>
		<option value="6"{if $calendar_month==6} selected{/if}>{translate name="JUNE"}June{/translate}</option>
		<option value="7"{if $calendar_month==7} selected{/if}>{translate name="JULY"}July{/translate}</option>
		<option value="8"{if $calendar_month==8} selected{/if}>{translate name="AUGUST"}August{/translate}</option>
		<option value="9"{if $calendar_month==9} selected{/if}>{translate name="SEPTEMBER"}September{/translate}</option>
		<option value="10"{if $calendar_month==10} selected{/if}>{translate name="OCTOBER"}October{/translate}</option>
		<option value="11"{if $calendar_month==11} selected{/if}>{translate name="NOVEMBER"}November{/translate}</option>
		<option value="12"{if $calendar_month==12} selected{/if}>{translate name="DEZEMBER"}Dezember{/translate}</option>
	</select>
	<select name="year">
		{for $year = $calendar_yearspan.min to $calendar_yearspan.max}
			<option value="{$year}"{if $calendar_year==$year} selected{/if}>{$year}</option>
		{/for}
	</select>
	<input type="submit" value="{translate name="GO"}Go{/translate}" />
	</form>
</div>
<table class="calendar">
	<tr class="header">
		<th class="dayname">&nbsp;</th>
		<th class="day">&nbsp;</th>
		<th class="name">{translate name="NAME"}Name{/translate}</th>
		<th class="time">{translate name="TIME"}Time{/translate}</th>
	</tr>
	{foreach $calendar_days as $daynum => $day}{assign var="daytime" value="{$calendar_year}-{$calendar_month}-{$daynum}"}
		<tr class="day day{$daytime|date_format:"%u"} {cycle values="odd,even"}">
			<td class="dayname">{$daytime|date_format:"%a"}</td>
			<td class="day">{$daytime|date_format:"%d"}</td>
			<td class="name">
				{foreach $day as $date}
					<a href="{$Core->url('index', 'view', 'webournal', ['id' => $date.id])}">{$date.name|escape:"htmlall"}</a>{if !$date@last}<br />{/if}
				{/foreach}
			</td>
			<td class="time">
				{foreach $day as $date}
					{$date.directory_time|date_format:"%H:%M"}{if !$date@last}<br />{/if}
				{/foreach}
			</td>
		</tr>
	{/foreach}
</table>
