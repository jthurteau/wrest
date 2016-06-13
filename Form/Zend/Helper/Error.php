<?php
/**
 * copied from Zend_Form_Decorator_Errors
 *
 * Any options passed will be used as HTML attributes of the ul tag for the errors.
 *
 * @category   Zend
 * @package    Zend_Form
 * @subpackage Decorator
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
class Saf_Form_Zend_Helper_Error extends Zend_Form_Decorator_Abstract
{
    /**
     * Render errors
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
        $element = $this->getElement();
        $view    = $element->getView();
        if (NULL === $view) {
            return $content;
        }
        // Get error messages
        if ($element instanceof Zend_Form
            && NULL !== $element->getElementsBelongTo()
        ) {
 ?>
 <!-- <?php print_r(array('a',get_class($element),get_class($element->getElementsBelongTo()))); ?> -->
 <?php 
            $errors = $element->getMessages(NULL, TRUE);
        } else {
 ?>
 <!-- <?php print_r(array('b',get_class($element),get_class($element->getElementsBelongTo()))); ?> -->
 <?php 
            $errors = $element->getMessages();
        }
        if (empty($errors)) {
            return $content;
        }

        $separator = $this->getSeparator();
        $placement = $this->getPlacement();
        if (is_array($errors)) {
        	$errorsMessage = '';
        	foreach($errors as $error) {
        		$errorsMessage .= $view->formErrors($error, $this->getOptions());
        	}
        	$errors = $errorsMessage; //#TODO #2.0.0 see if we can't get rid of this...
        } else {
        	$errors    = $view->formErrors($errors, $this->getOptions());
        }
        

        switch ($placement) {
            case self::APPEND:
                return $content . $separator . $errors;
            case self::PREPEND:
                return $errors . $separator . $content;
        }
    }
}