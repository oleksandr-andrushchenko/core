<?php

namespace SNOWGIRL_CORE\View\Widget\Form;

use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Form;

class Input extends Widget
{
    protected $name;
    protected $value;
    protected $type = 'text';
    protected $attrs = [];
    //@todo...
    protected $required;

    protected function makeParams(array $params = []): array
    {
        $params = parent::makeParams($params);

        if (!isset($params['name'])) {
            throw new Exception('invalid "name" tag input param');
        }

        return $params;
    }

    protected function makeValue()
    {
        return $this->getValue();
    }

    protected function getValue($array = false)
    {
        $output = $this->value;

        if ($array) {
            if (!is_array($output)) {
                $output = [$output];
            }

            $output = array_filter($output, function ($v) {
                return null !== $v;
            });
        } else {
            if ($output) {
                if (is_array($output)) {
                    $output = $output[0];
                }
            } else {
                $output = null;
            }
        }

        return $output;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function dropValue()
    {
        return $this->setValue(null);
    }

    public function addValue($value)
    {
        $output = $this->getValue(true);
        $output[] = $value;
        $this->setValue($output);
        return $this;
    }

    public function addValues(array $values)
    {
        foreach ($values as $value) {
            $this->addValue($value);
        }

        return $this;
    }

    protected function getNode(): ?Node
    {
//        $this->addDomClass('form-control');

        return $this->makeNode('input', array_merge([
            'autocomplete' => 'off',
            'class' => 'form-control ' . $this->getDomClass(),
            'name' => $this->name,
            'id' => $this->getDomId(),
            'value' => $this->makeValue(),
            'placeholder' => $this->makeText($this->name . '_placeholder'),
            'aria-label' => $this->makeText($this->name),
            'type' => $this->type
        ], $this->attrs));
    }

    protected function getInner(string $template = null): ?string
    {
        return null;
    }

    protected function addScripts(): Widget
    {
        return parent::addScripts()
            ->addCoreScripts()
            ->addJsScript('@core/widget/input.js');
    }

    public function triggerCloneCallback()
    {
        $this->dropValue();
        parent::triggerCloneCallback();
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->parent;
    }
}