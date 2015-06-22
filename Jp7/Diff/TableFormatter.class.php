<?php


/**
 * Wikipedia Table style diff formatter.
 *
 * @todo document
 * @private
 * @ingroup DifferenceEngine
 */
class Jp7_Diff_TableFormatter extends Jp7_Diff_Formatter
{
    public function __construct()
    {
        $this->leading_context_lines = 2;
        $this->trailing_context_lines = 2;
    }

    public static function escapeWhiteSpace($msg)
    {
        $msg = preg_replace('/^ /m', '&nbsp; ', $msg);
        $msg = preg_replace('/ $/m', ' &nbsp;', $msg);
        $msg = preg_replace('/  /', '&nbsp; ', $msg);

        return $msg;
    }

    public function _block_header($xbeg, $xlen, $ybeg, $ylen)
    {
        $r = '<tr><td colspan="2" class="diff-lineno"><!--LINE '.$xbeg."--></td>\n".
          '<td colspan="2" class="diff-lineno"><!--LINE '.$ybeg."--></td></tr>\n";

        return $r;
    }

    public function _start_block($header)
    {
        echo $header;
    }

    public function _end_block()
    {
    }

    public function _lines($lines, $prefix = ' ', $color = 'white')
    {
    }

    # HTML-escape parameter before calling this
    public function addedLine($line)
    {
        return $this->wrapLine('+', 'diff-addedline', $line);
    }

    # HTML-escape parameter before calling this
    public function deletedLine($line)
    {
        return $this->wrapLine('-', 'diff-deletedline', $line);
    }

    # HTML-escape parameter before calling this
    public function contextLine($line)
    {
        return $this->wrapLine(' ', 'diff-context', $line);
    }

    private function wrapLine($marker, $class, $line)
    {
        if ($line !== '') {
            // The <div> wrapper is needed for 'overflow: auto' style to scroll properly
            $line = Jp7_Diff_Xml::tags('div', null, $this->escapeWhiteSpace($line));
        }

        return "<td class='diff-marker'>$marker</td><td class='$class'>$line</td>";
    }

    public function emptyLine()
    {
        return '<td colspan="2">&nbsp;</td>';
    }

    public function _added($lines)
    {
        foreach ($lines as $line) {
            echo '<tr>'.$this->emptyLine().
            $this->addedLine('<ins class="diffchange">'.
            htmlspecialchars($line, ENT_COMPAT | ENT_HTML401, 'ISO-8859-1').'</ins>')."</tr>\n";
        }
    }

    public function _deleted($lines)
    {
        foreach ($lines as $line) {
            echo '<tr>'.$this->deletedLine('<del class="diffchange">'.
            htmlspecialchars($line, ENT_COMPAT | ENT_HTML401, 'ISO-8859-1').'</del>').
            $this->emptyLine()."</tr>\n";
        }
    }

    public function _context($lines)
    {
        foreach ($lines as $line) {
            echo '<tr>'.
            $this->contextLine(htmlspecialchars($line, ENT_COMPAT | ENT_HTML401, 'ISO-8859-1')).
            $this->contextLine(htmlspecialchars($line, ENT_COMPAT | ENT_HTML401, 'ISO-8859-1'))."</tr>\n";
        }
    }

    public function _changed($orig, $closing)
    {
        //wfProfileIn( __METHOD__ );

        $diff = new Jp7_Diff_WordLevel($orig, $closing);
        $del = $diff->orig();
        $add = $diff->closing();

        # Notice that WordLevelDiff returns HTML-escaped output.
        # Hence, we will be calling addedLine/deletedLine without HTML-escaping.

        while ($line = array_shift($del)) {
            $aline = array_shift($add);
            echo '<tr>'.$this->deletedLine($line).
            $this->addedLine($aline)."</tr>\n";
        }
        foreach ($add as $line) {    # If any leftovers
            echo '<tr>'.$this->emptyLine().
            $this->addedLine($line)."</tr>\n";
        }
        //wfProfileOut( __METHOD__ );
    }
}
