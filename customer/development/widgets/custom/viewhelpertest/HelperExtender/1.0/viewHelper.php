<?

namespace Custom\Helpers;

class HelperExtenderHelper {
    /**
     * Overrides ViewHelper#field
     */
    function field() {
        return <<<HOUSE
        ~~
      ~
    _u__
   /____\
   |[][]|
   |[]..|
   '--'''

HOUSE;
    }
}
