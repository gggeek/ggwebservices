<?php
/**
 * A wrapper for XML objects. Tries to be the SimpleXML for templates.
 *
 * Most of the time accessing attributes or children elements of an xml element
 * is as simple as writing  $element.childname and $element.attributename.
 * Child elements that contain pure text (no attributes, no children) are returned
 * as strings; other elements as ggSimpleTemplateXML objects.
 * Attributes are always returned as strings.
 * To access the text portion of an element use $element.textContent
 * If an xml element has an attribute or a child named either 'textContent', 'children' or 'attributes',
 * to access its content do not go via $element.textContent but instead via
 * $element.children.textContent or $element.attributes.textContent.
 * The same applies if an element has both an attribute and a child element with
 * the same name: in this case to access the child use $element.children.childname
 *
 * @author G. Giunta
 * @copyright (C) 2011-2016 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
 *
 * @todo give a look at xpath usage for attribute(): is it faster?
 * @todo test: how are the names of subelements/attributes shown when there is a namespace?
 */

class ggSimpleTemplateXML
{
    protected $node = null;
    protected static $meta_attributes = array( 'textContent', 'children', 'attributes');

    function __construct( $domnode )
    {
        $this->node = $domnode;
    }

    function hasAttribute( $name )
    {
        if ( in_array( $name, self::$meta_attributes ) )
        {
            return true;
        }
        if ( $this->node->hasAttribute( $name ) )
        {
            return true;
        }
        foreach( $this->node->childNodes as $childNode )
        {
            if ( $childNode->nodeType == XML_ELEMENT_NODE && $childNode->tagName == $name )
            {
                return true;
            }
        }
        return false;
    }

    function attributes()
    {
        $results = self::$meta_attributes;

        $alength = $this->node->attributes->length;
        for ( $i = 0; $i < $alength; ++$i )
        {
            $results[] = $this->node->attributes->item($i)->name;
        }

        foreach( $this->node->childNodes as $childNode )
        {
            if ( $childNode->nodeType == XML_ELEMENT_NODE )
            {
                $results[] = $childNode->tagName;
            }
        }

        return array_unique( $results );
    }

    function attribute( $name )
    {
        // 1st go for the known elements
        switch( $name )
        {
            case 'textContent':
                return $this->node->textContent;

            case 'attributes':
                $results = array();
                $alength = $this->node->attributes->length;
                for ( $i = 0; $i < $alength; ++$i )
                {
                    $attr = $this->node->attributes->item($i);
                    $results[$attr->name] = $attr->value;
                }
                return $results;

            case 'children':
                $found = array();
                foreach( $this->node->childNodes as $childNode )
                {
                    if ( $childNode->nodeType == XML_ELEMENT_NODE )
                    {
                        // take into account that we might return an array here,
                        // if we find more than one child elements with the same name
                        if ( isset( $results[$childNode->tagName] ) )
                        {
                            if ( is_array( $results[$childNode->tagName] ) )
                            {
                                // 3rd child with same name and on
                                $results[$childNode->tagName][] = self::domNode2STX( $childNode );
                            }
                            else
                            {
                                // 2nd child with same name
                                $results[$childNode->tagName] = array( $results[$childNode->tagName], self::domNode2STX( $childNode ) );
                            }
                        }
                        else
                        {
                            // 1st child found with given name
                            $results[$childNode->tagName] = self::domNode2STX( $childNode );
                        }
                    }
                }
                return $results;
        }

        // then look in attributes, as it's most likely faster than going through children
        if ( $this->node->hasAttribute( $name ) )
        {
            return $this->node->getAttribute( $name );
        }

        // if attribute with correct name is not there, look in children
        // NB: we might have more than one child with that name
        $found = array();
        foreach( $this->node->childNodes as $childNode )
        {
            if ( $childNode->nodeType == XML_ELEMENT_NODE && $childNode->tagName == $name )
            {
                $found[] = self::domNode2STX( $childNode );
            }
        }
        // if we found a list of elements we return as an array
        if ( count( $found ) > 1 )
        {
            return $found;
        }
        // single element we return as is
        if ( count( $found ) == 1 )
        {
            return $found[0];
        }

    }

    /**
    * Try a useful conversion to an array structure. Useful for eg. dump calls
    */
    function toArray()
    {
        $out = array ();

        $attributes = $this->attribute( 'attributes' );
        if ( count( $attributes ) )
        {
            $out['attributes'] = $attributes;
        }

        $children = $this->attribute( 'children' );
        foreach( $children as $name => $value )
        {
            if ( is_object( $value ) )
            {
                $out['children'][$name] = $value->toArray();
            }
            else if ( is_array( $value ) )
            {
                foreach( $value as $key => $val )
                {
                    if ( is_object( $val ) )
                    {
                        $value[$key] = $val->toArray();
                    }
                }
                $out['children'][$name] = $value;
            }
            else
            {
                $out['children'][$name] = $value;
            }
        }

        if ( !count( $children ) )
        {
            $textContent = $this->attribute( 'textContent' );
            if ( $textContent != '' )
            {
                $out['textContent'] = $textContent;
            }
        }

        return $out;
    }

    /**
    * Returns the dom node wrapped by this object as a SimpleXML object
    */
    function toSimpleXML()
    {
        return simplexml_import_dom( $this->node );
    }

    /**
    * Returns either a ggSimpleTemplateXML wrapping a dom element or a string,
    * based on whether the element has any attribute or child or none
    */
    protected static function domNode2STX( $node )
    {
        if ( $node->hasAttributes() )
        {
            return new ggSimpleTemplateXML( $node );
        }
        foreach( $node->childNodes as $childNode )
        {
            if ( $childNode->nodeType == XML_ELEMENT_NODE )
            {
               return new ggSimpleTemplateXML( $node );
            }
        }
        return $node->textContent;
    }
}

?>