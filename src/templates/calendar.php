<?php //#TODO KNOWN BUGS
(isset($disableLayout) && $disableLayout) 
    || $this->layout('layout::default', ['title' => $t('applicationName')]);

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
$forwardLink = implode('/',$forward) . '/';
if (isset($returnLink) && $returnLink) {
?>
    <div class="row calendarRow">
        <div class="small-12 columns">
            <a href="<?php print($returnLink); ?>"><?php print($t('return-to-app')); ?></a>
        </div>
    </div>
<?php
}
?>

<?php
    if ($calendarModel && $calendarModel->fullView()) {
?>
    <div class="row calendarRow">
<?php
        for($i = -5; $i <=5; $i++){
            $hideClass =
                abs($i) > 2
                ? ' hide-for-small'
                : '';
            $endClass = $i == 5 ? ' end': '';
            $firstClass = $i == -5 ? ' halfPushMargin' : '';
            $yearString = $year + $i;
            $yearLink = "calendar/{$yearString}/{$forwardLink}";
            $yearDisabled = $calendarModel && !$calendarModel->allowedYear($yearString);
            $yearClass = (
                    $yearDisabled
                    ? ' disabled'
                    : ''
                ) . (
                    $yearString == $year
                    ? ' current'
                    : ''
                );
    
?>
        <div class="small-2 medium-1 centerAlign columns<?php print($hideClass . $endClass . $firstClass); ?>">
<?php
            if ($yearDisabled || $yearString == $year) {
?>
            <span class="buttonLink calendarButton fullWidthButton bottomMargin<?php print($yearClass);?>"><?php print($yearString);?></span>
<?php
            } else {
?>
            <a class="buttonLink calendarButton fullWidthButton bottomMargin<?php print($yearClass);?>" href="<?php Saf\Util\Layout::printLink($yearLink);?>"><?php print($yearString);?></a>
<?php
            }
?>
        </div>
<?php 
        }
?>
    </div>
    <div class="row hide-for-medium-up calendarRow">
<?php
        $specialClass = '';
        for($i = -2; $i <=2; $i++){
            $month = $month + $i;
            $yearString = $year;
            if ($month < 1) {
                $month = 12 + $month;
                $yearString--;
            } else if ($month > 12) {
                $month = $month - 12;
                $yearString++;
            }
            $endClass = $i == 2 ? ' end': '';
            $monthString = $monthList[$month];
            $monthNumber = str_pad($month, 2, '0', STR_PAD_LEFT);
            $monthLink = "calendar/{$yearString}-{$monthNumber}/{$forwardLink}";
            $monthDisabled = $calendarModel && !$calendarModel->allowedMonth($monthNumber, $yearString);
            $monthClass = (
                    $monthDisabled
                    ? ' disabled'
                    : ''
                ) . (
                    $monthNumber == $month
                    ? ' current'
                    : ''
                );
?>
        <div class="small-2 centerAlign columns<?php print($specialClass . $endClass);?>">
<?php
            if ($monthDisabled || $monthNumber == $month) {
?>
            <span class="buttonLink calendarButton fullWidthButton bottomMargin<?php print($monthClass);?>"><?php print($monthString);?></span>
<?php
            } else {
?>
            <a class="buttonLink calendarButton fullWidthButton bottomMargin<?php print($monthClass);?>" href="<?php Saf\Util\Layout::printLink($monthLink);?>"><?php print($monthString);?></a>
<?php
            }
?>
        </div>
<?php 
            $specialClass = '';
        }
?>
    </div>
    <div class="row hide-for-small calendarRow">
<?php
        for($i = 1; $i < 13; $i++){
            $endClass = $i == 12 ? ' end': '';
            $monthString = $monthList[$i];
            $monthNumber = str_pad($i, 2, '0', STR_PAD_LEFT);
            $yearString = $year;
            $monthLink = "calendar/{$yearString}-{$monthNumber}/{$forwardLink}";
            $monthDisabled =$calendarModel && !$calendarModel->allowedMonth($monthNumber, $yearString);
            $monthClass = (
                    $monthDisabled
                    ? ' disabled'
                    : ''
                ) . (
                    $monthNumber == $month
                    ? ' current'
                    : ''
                );
?>
        <div class="small-2 medium-1 columns <?php print($endClass);?>">
<?php
        if ($monthDisabled || $monthNumber == $month) {
?>
            <span class="buttonLink calendarButton fullWidthButton bottomMargin<?php print($monthClass);?>"><?php print($monthString);?></span>
<?php
        } else {
?>
            <a class="buttonLink calendarButton fullWidthButton bottomMargin<?php print($monthClass);?>" href="<?php Saf\Util\Layout::printLink($monthLink);?>"><?php print($monthString);?></a>
<?php
        }
?>
        </div>
        
<?php 
        }
?>
    </div>
<?php
    } else {
        $current = strtotime("{$year}-{$month}-{$day}");
        $userDate = $calendarModel->getUserMonthYear($current);
        $nextMonth =
            $month < 12
            ? ($year . '-' . str_pad($month + 1, 2, '0', STR_PAD_LEFT))
            : (($year + 1) . '-01');
        $next = strtotime("{$nextMonth}-01");
        $userNextMonth =$calendarModel->getUserMonthYear($next);
        $nextLink = "calendar/{$nextMonth}/{$forwardLink}";
        $prevMonth =
            $month > 1
                ? ($year . '-' . str_pad($month - 1, 2, '0', STR_PAD_LEFT))
                : (($year - 1) . '-12');
        $prev = strtotime("{$prevMonth}-01");
        $userPrevMonth = $calendarModel->getUserMonthYear($prev);
        $prevLink = "calendar/{$prevMonth}/{$forwardLink}";
?>
    <div class="row calendarShortNav">
        <div class="small-2 columns">
<?php
        $allowedYear = (int)substr($prevMonth,0,4);
        $allowedMonth = (int)substr($prevMonth,5,2);
        if ($calendarModel->allowedMonth($allowedMonth, $allowedYear)) {
?>
            <a href="<?php Saf\Util\Layout::printLink($prevLink); ?>" class="largestFontSize noDecoration">
                <span class="fa fa-chevron-left fi-arrow-left" title="<?php print($userPrevMonth); ?>"><span class="accessibleHidden"><?php print($userPrevMonth); ?></span></span>
            </a>
<?php
        }
?>
        &nbsp;</div>
        <div class="small-8 columns centerAlign"><span class="largestFontSize"><?php print($userDate); ?></span></div>
        <div class="small-2 columns rightAlign">&nbsp;
<?php
        $allowedYear = (int)substr($nextMonth,0,4);
        $allowedMonth = (int)substr($nextMonth,5,2);
        if ($calendarModel->allowedMonth($allowedMonth, $allowedYear)) {
?>
            <a href="<?php Saf\Util\Layout::printLink($nextLink); ?>" class="largestFontSize noDecoration">
                <span class="fa fa-chevron-right fi-arrow-right" title="<?php print($userNextMonth); ?>"><span class="accessibleHidden"><?php print($userNextMonth); ?></span></span>
            </a>
<?php
        }
?>
        </div>
    </div>
<?php
    }
?>
    <div class="row topMargin calendarWeekRow">
<?php
    for($i = 0; $i < 7; $i++){
        $endClass = $i == 6 ? ' end': '';
        $specialClass = '';
?>
        <div class="small-1 columns bottomMargin<?php print($specialClass . $endClass);?>">
            <?php print($weekDay[$i]);?>
        </div>
        
<?php 
    }
    $s = 1;
    $firstMonthDate = strtotime("{$year}-{$month}-01");
    $t = date('t', $firstMonthDate);
    $firstMonthWeekDay = date('D',$firstMonthDate);
    $firstMonthIndex = array_keys($weekDay,$firstMonthWeekDay);
    for($i = 0; $i < 6 && $s <= $t; $i++){
        for($j = 0; $j < 7; $j++){
            $endClass = $j == 7 ? ' end': '';
            if ($j == 0) {
                $specialClass = ' clearing';
            } else {
                $specialClass = '';
            }
            
            $yearString = $year;
            $monthNumber = str_pad($month, 2, '0', STR_PAD_LEFT);
            $dayNumber = str_pad($s, 2, '0', STR_PAD_LEFT);
            $dayLink = "{$forwardLink}{$yearString}-{$monthNumber}-{$dayNumber}";
            $dayDisabled = $calendarModel && !$calendarModel->allowedDate($dayNumber, $month, $year);
            $dayClass = (
                    $dayDisabled
                    ? ' disabled'
                    : ''
                ) . (
                $dayNumber == $day
                    ? ' current fullWidthButton'
                    : ' calendarButton fullWidthButton'
                );
?>
        <div class="small-1 columns<?php print($specialClass . $endClass); ?>">
<?php 
            if (
                $s <= $t
                && (
                    $s > 1
                    || $firstMonthIndex[0] == $j
                )
            ){
                if ($dayDisabled) {
?>
            <span class="buttonLink calendarButton fullWidthButton bottomMargin<?php print($dayClass);?>">
                <?php print($s++); ?>
            </span>
<?php
                } else {
?>
            <span class="halfTopMargin"><a class="buttonLink <?php print($dayClass); ?>" href="<?php Saf\Util\Layout::printLink($dayLink); ?>">
                <?php print($s++); ?>
            </a></span>
<?php
                }
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

<?php 
if (isset($pageTitle) && $pageTitle) {
    $this->insert('app::page-title.partial', ['pageTitle' => $pageTitle]); 
}
?>

<div class="row">
    <div id="schedule-wrapper" class="small-12 medium-12 columns">
<?php
    if (isset($form)) {
?>
        <div class="row">
            <div class="small-12 medium-12 columns">
<?php
        $form->render();
?>
            </div>
        </div>
<?php
    }
?>
    </div>
</div>