{**
 * Template that includes the javascript code exposing ws functionality to js.
 * We do it 'the ezjsc way', and also 'the plain way', thus including some
 * non-template javascript here
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 *
 * @todo add a check to see if param 0 is set and a valid protocol...
 * @todo test if in non-vhost mode ezdesign is good enough or if it has to be stripped of ezroot('no', 'full')
 *}
{include uri=concat('file:', concat('javascript/yui/3.0/build/io/', $arguments.0, '.js')|ezdesign( 'no' )|extract(1))}
