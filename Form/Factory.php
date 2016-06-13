<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for forms

*******************************************************************************/

class Saf_Form_Factory
{
    protected $_config = NULL;

    const MAX_DEREF_DEPTH = 10;

	private function __construct(){
        }

	public static function build($formConfig){
                $newForm = new Zend_Form();
				$newForm->setConfig($formConfig);
                $newForm->removeDecorator('HtmlTag');
                $newFormId = $newForm->getId();
                $formDecorator = $newForm->getDecorator('Form');
                $elementDecorator = $newForm->getDecorator('FormElements');
                $newForm->setDecorators(array(
                        new Saf_Form_Zend_Helper_Error(//Zend_Form_Decorator_Errors(
                            array(
                               'class' => 'formErrors',
                                'id' => $newFormId . 'Errors',
                                'placement' => 'append',
                                'escape'=>false
                            )
                        ),
                        $elementDecorator,
                        $formDecorator

                ));
                foreach($newForm->getElements() as $element) {
                        self::fixElement($element);
                }
                return $newForm;
        }

	public static function fixElement($element){
                $element->removeDecorator('DtDdWrapper');
                $element->removeDecorator('HtmlTag');
                if($element->getAttrib('wrapperclass')){
                        $wrapperClass = 'formElementWrapper ' . $element->getAttrib('wrapperclass');
                        $element->addDecorator('HtmlTag',array(
                                'tag'=>'div',
                                'id'=>$element->getId().'Wrapper',
                                'class'=>$wrapperClass)
                        );
                        $element->setAttribs(
                                Saf_Array::excludeKeys('wrapperclass', $element->getAttribs())
                        );
                        unset($element->wrapperclass);
                } else {
                        $element->addDecorator('HtmlTag',array(
                                'tag'=>'div',
                                'id'=>$element->getId().'Wrapper',
                                'class' => 'formElementWrapper'
                        ));
                }
                $labelDecorator = $element->getDecorator('Label');
                if($labelDecorator) {
                        $labelDecorator->setTag('');
                }
        }

	public static function append(Zend_Form $form, $element){
                $form->addElement($element);
                self::fixElement($element);
        }

	public static function template($formName, $templateConfig = array()){
				//#TODO #1.1.0 path safe this
                if(!file_exists(APPLICATION_PATH . "/configs/form/{$formName}.xml")) {
                        throw new Exception("Requested form template, {$formName}, was not found.");
                }
                $rawXml = file_get_contents(APPLICATION_PATH . "/configs/form/{$formName}.xml");
                $dereferencedXml = Saf_Model_Reflector::dereference($rawXml, $templateConfig);
                return new Zend_Config_Xml($dereferencedXml);
        }
}

