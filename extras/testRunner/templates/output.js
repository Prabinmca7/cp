(function() {
    var passedSuites = Array.prototype.slice.call(document.getElementsByClassName('passed-suite')),
        passedCases = Array.prototype.slice.call(document.getElementsByClassName('passed-case')),
        resultContainer = document.getElementsByClassName('result-container')[0],
        passed = document.getElementsByClassName('title')[0].classList.contains('passed'),
        testList = document.getElementsByClassName('test-list')[0];

    function toggleClassList(items, className) {
        items.forEach(function(item) {
            item.classList.toggle(className);
        });
    }

    if(passed) {
        testList.classList.toggle('hidden');
    }

    document.getElementById('showPasses').addEventListener('click', function() {
        if(passed) {
            testList.classList.toggle('hidden');
        }

        toggleClassList(passedSuites, 'passed-suite');
        toggleClassList(passedCases, 'passed-case');
    });
})();