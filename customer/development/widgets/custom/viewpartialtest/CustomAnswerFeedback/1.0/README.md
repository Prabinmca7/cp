The purpose of this widget is to excercise widget view extension functionality.

This widget adds top and bottom blocks inside `view.php`.

The `buttonView.html.php` view adds a block before the buttons.

Since no other view partials exist inside this widget directory, the other stock AnswerFeedback widget partials should render without error.

Adding to a page:

    <rn:widget path="feedback/CustomAnswerFeedback" options_count="4"/>

Illustrates,

- Top and bottom blocks are inserted.
- Parent partials are used and render just fine when child doesn't have a partial with that name.

Adding to a page:

    <rn:widget path="feedback/CustomAnswerFeedback"/>

Illustrates,

- Top and bottom blocks are inserted.
- Parent partial is rendered with widget's partial blocks inserted.



ヾ(❛ε❛“)ʃ

