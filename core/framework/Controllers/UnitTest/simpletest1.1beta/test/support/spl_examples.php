<?php
    // $Id: spl_examples.php,v 1.1 2012/05/04 00:16:28 jwatson Exp $

    class IteratorImplementation implements Iterator {
        function current() { }
        function next() { }
        function key() { }
        function valid() { }
        function rewind() { }
    }

    class IteratorAggregateImplementation implements IteratorAggregate {
        function getIterator() { }
    }
?>