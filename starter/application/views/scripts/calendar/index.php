<div class="row">
<?php
$weekDay = array('Sun','Mon','Tue','Wed','Thu', 'Fri', 'Sat');
$monthList = array(
	1 => 'Jan',	
	2 => 'Feb',	
	3 => 'Mar',	
	4 => 'Apr',	
	5 => 'May',	
	6 => 'Jun',	
	7 => 'Jul',	
	8 => 'Aug',	
	9 => 'Sep',	
	10 => 'Oct',	
	11 => 'Nov',	
	12 => 'Dec',		
);
$forwardLink = implode('/',$this->forwardResource) . '/';
for($i = -5; $i <=5; $i++){
	$hideClass =
		abs($i) > 2
		? ' hide-for-small'
		: '';
	$endClass = $i == 5 ? ' end': '';
	$yearString = $this->year + $i;
	$yearLink = "calendar/{$yearString}/{$forwardLink}";
?>
	<div class="small-2 medium-1 centerAlign columns<?php print($hideClass . $endClass);?>">
		<a class="buttonLink secondaryButton bottomMargin" href="<?php Saf_Layout::printLink($yearLink);?>"><?php print($yearString);?></a>
	</div>
<?php 
}
?>
</div>
<div class="row hide-for-medium-up">
<?php
$specialClass = '';
for($i = -2; $i <=2; $i++){
	$month = $this->month + $i;
	if ($month < 1) {
		$month = 12 + $month;
	} else if ($month > 12) {
		$month = $month - 12;
	}
	$endClass = $i == 2 ? ' end': '';
	$monthString = $monthList[$month];
	$monthNumber = str_pad($month, 2, '0', STR_PAD_LEFT);
	$yearString = $this->year;
	$monthLink = "calendar/{$yearString}-{$monthNumber}/{$forwardLink}";
?>
	<div class="small-2 centerAlign columns<?php print($specialClass . $endClass);?>">
		<a class="buttonLink secondaryButton bottomMargin" href="<?php Saf_Layout::printLink($monthLink);?>"><?php print($monthString);?></a>
	</div>
<?php 
	$specialClass = '';
}
?>
</div>
<div class="row hide-for-small">
<?php
for($i = 1; $i < 13; $i++){
	$endClass = $i == 12 ? ' end': '';
	$monthString = $monthList[$i];
	$monthNumber = str_pad($i, 2, '0', STR_PAD_LEFT);
	$yearString = $this->year;
	$monthLink = "calendar/{$yearString}-{$monthNumber}/{$forwardLink}";
?>
	<div class="small-2 medium-1 columns <?php print($endClass);?>">
		<a class="buttonLink secondaryButton bottomMargin" href="<?php Saf_Layout::printLink($monthLink);?>"><?php print($monthList[$i]);?></a>
	</div>
	
<?php 
}
?>
</div>
<div class="row">
<?php
for($i = 0; $i < 7; $i++){
	$endClass = $i == 6 ? ' end': '';
	$specialClass = ' small-push-1';
?>
	<div class="small-1 columns bottomMargin<?php print($specialClass . $endClass);?>">
		<?php print($weekDay[$i]);?>
	</div>
	
<?php 
}
$s = 1;
$t = date('t',$this->date);
$firstMonthDate = strtotime("{$this->year}-{$this->month}-01");
$firstMonthWeekDay = date('D',$firstMonthDate);
$firstMonthIndex = array_keys($weekDay,$firstMonthWeekDay);
Saf_Debug::outData(array($firstMonthIndex[0]));
for($i = 1; $i < 6 && $s <= $t; $i++){
	for($j = 1; $j < 8; $j++){
		$endClass = $j == 8 ? ' end': '';
		if ($j == 1) {
			$specialClass = ' small-push-1 clearing';
		} else {
			$specialClass = ' small-push-1';
		}
		
		$yearString = $this->year;
		$monthNumber = str_pad($this->month, 2, '0', STR_PAD_LEFT);
		$dayNumber = str_pad($s, 2, '0');
		$dayLink = "{$forwardLink}{$yearString}-{$monthNumber}-{$dayNumber}";
	?>
	<div class="small-1 columns<?php print($specialClass . $endClass); ?>">
<?php 
		if (
			$s <= $t
			&& (
				$s > 1
				|| $firstMonthIndex[0] == $j - 1
			)
		){
?>
		<span class="halfTopMargin"><a class="buttonLink calendarButton" href="<?php Saf_Layout::printLink($dayLink); ?>">
<?php 
			print($s++);
?>	
		</a></span>
<?php 

		} else {
?>
		<span class="halfTopMargin">&nbsp;</span>
<?php 

		}
?>
	</div>
<?php 
	}
}
?>
</div>