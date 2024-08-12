<rn:block id="top"/>
<? $initialFocus = $this->data['attrs']['initial_focus_on_first_field'] ? 'true' : 'false'; ?>
<rn:block id="preFirstName"/>
<rn:widget path="input/FormInput" name="#rn:php:$this->data['names'][0]#" initial_focus="#rn:php:$initialFocus#" label_input="#rn:php:$this->data['labels'][0]#" sub_id="first"/>
<rn:block id="postFirstName"/>
<? if (!$this->data['attrs']['short_name']): ?>
<rn:block id="preLastName"/>
<rn:widget path="input/FormInput" name="#rn:php:$this->data['names'][1]#" label_input="#rn:php:$this->data['labels'][1]#" sub_id="last"/>
<rn:block id="postLastName"/>
<? endif; ?>
<rn:block id="bottom"/>
