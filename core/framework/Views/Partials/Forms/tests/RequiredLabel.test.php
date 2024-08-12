<?php

class TestRequiredLabelPartial extends ViewPartialTestCase {
    public $testingClass = 'Partials/Forms/RequiredLabel';

    function defaultState () {
        return $this->render();
    }

    function passedLabels () {
        return $this->render(array(
            'requiredLabel'     => 'food',
            'screenReaderLabel' => 'curly',
        ));
    }

    function testDefaultState () {
        $this->assertViewIsUnchanged('defaultState');
    }

    function testPassedLabels () {
        $this->assertViewIsUnchanged('passedLabels');
    }
}
