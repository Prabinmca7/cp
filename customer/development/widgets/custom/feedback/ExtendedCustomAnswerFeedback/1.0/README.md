The purpose of this widget is to excercise multiple widget view extension functionality.

This widget adds a top block inside `view.php`.

The `ratingMeter.html.php` view adds a block before and after the rating scale.

Since no other view partials exist inside this widget directory, the stock AnswerFeedback's widget partials should render without error and CustomAnswerFeedback's `buttonView.html.php` blocks should render without error.

Adding to a page:

    <rn:widget path="feedback/ExtendedCustomAnswerFeedback" options_count="4"/>

Illustrates,

- Top block in view.php is used--overridding CustomAnswerFeedback's top view.php block.
- Blocks in `ratingMeter.html.php` render as expected.

Adding to a page:

    <rn:widget path="feedback/ExtendedCustomAnswerFeedback"/>

Illustrates,

- Top block in view.php is used--overridding CustomAnswerFeedback's top view.php block.
- Parent partial (buttonView.html.php) is used and renders just fine when the child doesn't have a partial with that name.



ヾ(❛ε❛“)ʃ

