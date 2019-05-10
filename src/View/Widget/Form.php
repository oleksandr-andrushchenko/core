<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 28.01.15
 * Time: 6:42
 * To change this template use File | Settings | File Templates.
 */
namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\DateTime as DateTimeValue;
use SNOWGIRL_CORE\Script\Css;
use SNOWGIRL_CORE\Script\Js;
use SNOWGIRL_CORE\Service\Logger;
use SNOWGIRL_CORE\View;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\Exception\Form as FormException;
use SNOWGIRL_CORE\View\Widget\Form\Input;
use SNOWGIRL_CORE\View\Widget\Google\Captcha;

/**
 * Class Form
 * @package SNOWGIRL_CORE\View\Widget
 */
class Form extends Widget
{
    protected $action;
    protected $method;
    protected $offset = 3;
    protected $length = 7;
    /** @var Node[] */
    protected $buttons = [];
    protected $inline = false;
    /** @var Input[] */
    protected $inputs = [];

    protected $validatingRules = [];
    protected $validatingInvalidMessages = [];

    public function __construct(App $app, array $params = [], View $parent = null)
    {
        parent::__construct($app, $params, $parent);

        $this->addValidatingRules();

        if ($this->isValidating()) {
            $this->addClientValidatingScripts();
        }
    }

    protected function makeTemplate()
    {
        return str_replace('widget/form.', 'widget/form/', parent::makeTemplate());
    }

    public function triggerCloneCallback()
    {
        if (is_array($this->inputs)) {
            foreach ($this->inputs as $k => $v) {
                $v->triggerCloneCallback();
            }
        }

        parent::triggerCloneCallback();
    }

    public function exception($field, $message)
    {
        $this->addValidatingInvalidMessage($field, $message);
        return new FormException($field, $message, $this);
    }

    protected function addClientValidatingScripts()
    {
        if ($layout = $this->getLayout()) {
            $layout->addHeadCss(new Css('@core/form-validation.min.css'))
                ->addJs(new Js('@core/form-validation.min.js'))
                ->addJs(new Js('@core/form-validation.bootstrap.min.js'));
        }

        return $this;
    }

    public function fromRequest($k, $d = null)
    {
        return $this->app->request->get($k, $d);
    }

    public function getDateFromRequest($k, $d = null)
    {
        /** @var DateTimeValue $class */
        $class = $this->app->findClass('DateTime');
        return ($v = $this->fromRequest($k)) ? $class::createFromFormat($this->makeLink('date_php_format'), $v) : $d;
    }

    public function getValidationJsOptions()
    {
        return [
            'fields' => $this->getValidatingJsRules(),
            'messages' => $this->getValidatingInvalidMessages()
        ];
    }

    protected function addValidatingRules()
    {

    }

    public function isValid()
    {
        foreach ($this->validatingRules as $field => $validators) {
            foreach ($validators as $validator => $options) {
                $v = $this->fromRequest($field);

                switch (true) {
                    case ($validator == 'required' && !$v):
                    case ($validator == 'email' && !filter_var($v, FILTER_VALIDATE_EMAIL)):
                    case ($validator == 'regexp' && !preg_match('/' . $options['pattern'] . '/', $v)):
                    case ($validator == 'date' && strlen($v) && !$this->getDateFromRequest($field)):
                    case ($validator == 'number' && $v && !filter_var($v, FILTER_VALIDATE_INT)):
                    case ($validator == 'value' && $v && array_key_exists('min', $options) && (int)$v < $options['min']):
                    case ($validator == 'value' && $v && array_key_exists('max', $options) && (int)$v > $options['max']):
                    case ($validator == 'price' && $v && !preg_match("/^[0-9]+(\.[0-9]{2})?$/", $v));
//                    case ($validator == 'phone' && $v && !$this->app->geo->isCountryPhone($this->fromRequest('country_iso', $this->app->request->getClient()->getCountryIso()), $v)):
                        $this->addValidatingInvalidMessage($field, $this->getValidatingRuleMessage($options));
                        break;
                    case ($validator == 'confirmation' && $this->fromRequest($field) != $this->fromRequest($field . '_confirmation')):
                        $this->addValidatingInvalidMessage($field . '_confirmation', $this->getValidatingRuleMessage($options));
                        break;
                    case ($validator == 'uri' && $v):
                        if (($p = parse_url($v)) && !isset($p['scheme'])) {
                            $v = 'http://' . $v;
                        }

                        if (!filter_var($v, FILTER_VALIDATE_URL)) {
                            $this->addValidatingInvalidMessage($field, $this->getValidatingRuleMessage($options));
                        }

                        break;
                    default:
                        break;
                }
            }
        }

        return 0 == count($this->getValidatingInvalidMessages());
    }

    public function addValidatingInvalidMessage($field, $message)
    {
        $this->validatingInvalidMessages[$field][] = $message;
        return $this;
    }

    public function getValidatingInvalidMessages()
    {
        return $this->validatingInvalidMessages;
    }

    protected function getValidatingRuleMessage($options)
    {
        if (is_string($options)) {
            return $options;
        }

        if (is_array($options) && array_key_exists('message', $options)) {
            return $options['message'];
        }

        return null;
    }

    protected function addValidatingRule($field, $validator, $options = [])
    {
        $this->validatingRules[$field][$validator] = $options;
        return $this;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function getOffsetNode($node = 'div')
    {
        return $this->makeNode($node, ['class' => 'col-lg-offset-' . $this->getOffset() . ' col-lg-' . $this->getLength()]);
    }

    public function getGroup($name, $label = false)
    {
        $node = $this->makeNode('div', ['class' => implode(' ', ['form-group', $name])]);

        if ($label) {
            return $node->append($this->getLabel($name, is_string($label) ? $label : null));
        }

        return $node;
    }

    public function getLabel($name, $text = null)
    {
        return $this->makeNode('label', ['class' => $this->inline ? '' : ('col-md-' . $this->getOffset() . ' control-label'), 'for' => $name])
            ->append($text ?: T($name));
    }

    public function getLegend($name)
    {
        return $this->makeNode('div', ['class' => 'row'])
            ->append($this->getOffsetNode('legend')
                ->append(T($name . '_legend')));
    }

    public function getInput($name, $value = null, array $attrs = [], $isGroup = false, $label = true)
    {
        $this->inputs[$name] = $node = $this->app->views->input([
            'name' => $name,
            'value' => $value,
            'attrs' => $attrs
        ], $this);

        return $isGroup ? $this->getGroup($name, $label)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function getControl($node, $isGroup = false)
    {
        return $this->inline ? $node : $this->makeNode('div', ['class' => 'col-md-' . (is_int($isGroup) ? $isGroup : $this->getLength())])
            ->append($node);
    }

    protected function _getDateTimeInput($name, DateTimeValue $value = null, $options = [], $isGroup = false)
    {
        $this->inputs[$name] = $node = $this->app->views->dateTimeInput(array_merge([
            'name' => $name,
            'value' => $value
        ], $options), $this);

        return $isGroup ? $this->getGroup($name, true)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function getTimeInput($name, DateTimeValue $value = null, $isGroup = false, $isClientWidget = true)
    {
        return $this->_getDateTimeInput($name, $value, [
            'isDate' => false,
            'js' => $isClientWidget,
            'client' => $isClientWidget
        ], $isGroup);
    }

    public function getDateInput($name, DateTimeValue $value = null, $isGroup = false, $isClientWidget = true)
    {
        return $this->_getDateTimeInput($name, $value, [
            'isTime' => false,
            'js' => $isClientWidget,
            'client' => $isClientWidget
        ], $isGroup);
    }

    public function getDateTimeInput($name, DateTimeValue $value = null, $isGroup = false, $isClientWidget = true)
    {
        return $this->_getDateTimeInput($name, $value, [
            'js' => $isClientWidget,
            'client' => $isClientWidget
        ], $isGroup);
    }

    public function getTextarea($name, $value = null, array $attrs = [], $isGroup = false)
    {
        $node = $this->makeNode('textarea', array_merge([
//            'autocomplete' => 'off',
            'class' => 'form-control',
            'name' => $name,
            'id' => $name,
            'placeholder' => T($name . '_placeholder'),
            'aria-label' => T($name),
            'rows' => 2
        ], $attrs))->append($value);

        return $isGroup ? $this->getGroup($name, true)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function getTagInput($name, $value = null, $uri = null, $multi = false, $isGroup = false)
    {
        $this->inputs[$name] = $node = $this->app->views->tagInput([
            'name' => $name,
            'value' => $value,
            'uri' => $uri,
            'multi' => $multi
        ], $this);

        return $isGroup ? $this->getGroup($name, true)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function getCountryInput($countryIso = null, $editable = true, $isGroup = false)
    {
//        $this->countryIso = $countryIso ?: ($this->app->request->getClient()->getCountryIso() ?: $this->app->geo->getClientCountryIso());
        $this->countryIso = $countryIso ?: $this->app->request->getClient()->getCountryIso();
        $this->editable = !!$editable;
        $this->names = $this->app->geo->getCountryNames();
        $node = $this->stringifyContent('@core/widget/form/input/country.phtml');

        return $isGroup ? $this->getGroup('country_iso', true)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function getCityInput($countryIso = null, $cityId = null, $isGroup = false)
    {
        $this->countryIso = $countryIso ?: $this->app->request->getClient()->getCountryIso();
        $this->cityId = $cityId ?: $this->app->request->getClient()->getCityId();
        $this->cityNames = $this->app->geo->getCityNames($this->countryIso);
        $node = $this->stringifyContent('@core/widget/form/input/city.phtml');

        return $isGroup ? $this->getGroup('country_iso', true)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function getPhoneInput($name = null, $countryIso = null, $value = null, $editable = true, $isGroup = false)
    {
        $this->name = $name = $name ?: 'phone';
        $this->countryIso = $countryIso ?: $this->app->request->getClient()->getCountryIso();
        $this->value = $value;
        $this->editable = !!$editable;
        $node = $this->stringifyContent('@core/widget/form/input/phone.phtml');

        return $isGroup ? $this->getGroup($name, true)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function getMediaInput($name = 'media_id', $value = null, $defaultValue = null, $isMultiple = true, $isCover = false, $isGroup = false, $isClientWidget = true)
    {
        $this->inputs[$name] = $node = $this->app->views->mediaInput([
            'name' => $name,
            'value' => $value,
            'isMultiple' => $isMultiple,
            'default' => $defaultValue,
            'isCover' => $isCover,
            'isClient' => $isClientWidget
        ], $this);

        return $isGroup ? $this->getGroup($name, true)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function getPlaceInput($name = 'place_name', array $value = [], $isGroup = false, $isClientWidget = true)
    {
        $this->inputs[$name] = $node = $this->app->views->placeInput([
            'name' => $name,
            'value' => $value,
            'isClient' => $isClientWidget
        ], $this);

        return $isGroup ? $this->getGroup($name, true)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function getCheckbox($name, $isChecked = false, $isGroup = false)
    {
        $node = $this->makeNode('div', ['class' => 'checkbox'])
            ->append($this->makeNode('label')
                ->append($this->makeNode('input', ['type' => 'checkbox', 'name' => $name, 'value' => 1, $isChecked ? 'checked' : '']))
                ->append(T($name)));

        if ($isGroup) {
            return $this->getGroup($name)
                ->append($this->getOffsetNode()
                    ->append($node));
        }

        return $node;
    }

    public function getSelect($name, $value = null, $options = [], array $attrs = [], $isGroup = false)
    {
        $node = $this->makeNode('select', array_merge([
            'class' => 'form-control',
            'name' => $name,
            'id' => $name
        ], $attrs));

        foreach ($options as $k => $v) {
            $node->append($this->makeNode('option', ['value' => $k, $value == $k ? 'selected' : ''])
                ->append(T($v)));
        }

        return $isGroup ? $this->getGroup($name, true)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function getRadio($name, $value = null, $options = [], $isGroup = false)
    {
        $node = $this->makeNode('div');

        foreach ($options as $k => $v) {
            $node->append($this->makeNode('label', ['class' => 'radio-inline'])
                ->append($this->makeNode('input', ['type' => 'radio', 'name' => $name, 'value' => $k, $value == $k ? 'checked' : '']))
                ->append(T($name . '_' . $k)));
        }

        return $isGroup ? $this->getGroup($name, true)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function getPriceInput($name, $value = null, $isGroup = false)
    {
        $node = $this->makeNode('div', ['class' => 'input-group'])
            ->append($this->makeNode('span', ['class' => 'input-group-addon'])
                ->append($this->makeNode('span', ['class' => 'fa fa-usd'])))
            ->append($this->getInput($name, $value));

        return $isGroup ? $this->getGroup($name, true)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function getImageInput($name, $value = null, $multi = false, $isGroup = false)
    {
        $this->inputs[$name] = $node = $this->app->views->imageInput([
            'name' => $name,
            'value' => $value,
            'multi' => $multi
        ], $this);

        return $isGroup ? $this->getGroup($name, true)
            ->append($this->getControl($node, $isGroup)) : $node;
    }

    public function addButton($text, $color = 'default', $icon = null, $type = 'button')
    {
        $this->buttons[] = $this->getButton($text, $color, $icon, $type);
        return $this;
    }

    public function getButton($text, $color = 'default', $icon = null, $type = 'button')
    {
        return $this->makeNode('button' == $type ? 'a' : 'button', ['class' => 'btn btn-' . $color, 'type' => $type])
            ->append($icon ? $this->makeNode('span', ['class' => 'fa fa-' . $icon]) : '')
            ->append($text);
    }

    public function getButtons($isGroup = false)
    {
        $s = '';

        foreach ($this->buttons as $button) {
            $s .= ' ' . $button->stringify();
        }

        if ($isGroup) {
            $s = $this->makeNode('div', ['class' => 'form-group'])
                ->append($this->getOffsetNode()
                    ->append($s));
        }

        $this->buttons = [];
        return $s;
    }

    protected $captcha;

    /**
     * @return Captcha
     */
    public function getCaptcha()
    {
        if ($this->captcha) {
            if (!isset($this->inputs['captcha'])) {
                $this->inputs['captcha'] = $this->app->views->googleCaptcha($this->getLayout());
            }

            return $this->inputs['captcha'];
        }

        return false;
    }

    protected function addTexts()
    {
        return parent::addTexts()
            ->addText('widget.form');
    }

    public function getValidatingJsRules()
    {
        $rules = [];

        foreach ($this->validatingRules as $field => $validators) {
            $rules[$field] = ['validators' => []];

            foreach ($validators as $validator => $options) {
                $options = is_array($options) ? $options : ['message' => $options];

                switch ($validator) {
                    case 'required':
                        $validator = 'notEmpty';
                        break;
                    case 'length':
                        $validator = 'stringLength';
                        break;
                    case 'email':
                        $validator = 'emailAddress';
                        break;
                    case 'regexp':
                        if (array_key_exists('pattern', $options)) {
                            $options['regexp'] = $options['pattern'];
                            unset($options['pattern']);
                        } else {
                            $this->app->services->logger->make(sprintf('No pattern found for the form field(%s) validator(%s)', $field, $validator), Logger::TYPE_WARN);
                        }

                        break;
//                    case 'date':
//                        $options['format'] = $this->makeLink('date_validating_format');
//                        $options['separator'] = $this->makeLink('date_validating_separator');
//                        break;
                    case 'datetime':
                        $options['format'] = $this->makeLink('datetime_validating_format');
                        break;
                    case 'date':
                        $options['format'] = $this->makeLink('date_validating_format');
                        break;
                    case 'time':
                        $options['format'] = $this->makeLink('time_validating_format');
                        break;
                    case 'number':
                        $validator = 'integer';
                        break;
                    case 'value':
                        $isMin = array_key_exists('min', $options);
                        $isMax = array_key_exists('max', $options);

                        if ($isMin && $isMax) {
                            $validator = 'between';
                        } elseif ($isMin) {
                            $validator = 'greaterThan';
                            $options['value'] = $options['min'];
                            unset($options['min']);
                        } elseif ($isMax) {
                            $validator = 'lessThan';
                            $options['value'] = $options['max'];
                            unset($options['max']);
                        } else {
                            $this->app->services->logger->make(sprintf('No borders found for the form field(%s) validator(%s)', $field, $validator), Logger::TYPE_WARN);
                        }

                        $options['inclusive'] = true;
                        break;
                    case 'price':
                        $validator = 'regexp';
                        $options['regexp'] = '^[0-9]+(\.[0-9]{2})?$';
                        break;
                    case 'uri':
                        $options['allowEmptyProtocol'] = true;
                        $options['allowLocal'] = false;
                        $options['protocol'] = 'http,https';
                        break;
                    default:
                        break;
                }

                $rules[$field]['validators'][$validator] = $options;
            }
        }

        return $rules;
    }

    public function isValidating()
    {
        return $this->validatingRules;
    }

    protected function getNode()
    {
        return $this->makeNode('form', array_merge($this->attrs, [
            'action' => $this->action,
            'class' => implode(' ', [$this->inline ? 'form-inline' : 'form-horizontal', $this->getDOMClass()]),
            'id' => $this->getDomId(),
            'method' => $this->method,
            'role' => 'form'
        ]));
    }

    protected function getFormInner($template = null)
    {
        return $this->stringifyContent($template);
    }

    protected function getInner($template = null)
    {
        return implode('', [
            $this->getFormInner($template),
            $this->isValidating() ? $this->stringifyContent('@core/widget/form/validating.phtml') : '',
            $this->message ? $this->getGroup('', false)->append($this->getOffsetNode()->append($this->message)) : ''
        ]);
    }
}