{*
 @todo add support for passing GET params to both controller and action pages
 @todo add i18n of error message
 @todo auto vertical resizing of action frame (via js)
*}
<iframe name="frmcontroller" width="100%" height="385px" src={'/webservices/debugger/controller/controller/'|ezurl} marginwidth="0" marginheight="0" frameborder="0" scrolling="auto">
    Browser does not support Iframes. Debugger disabled.
</iframe>
<br/>
<iframe name="frmaction" width="100%" height="500px" src={'/webservices/debugger/action/'|ezurl} marginwidth="0" marginheight="0" frameborder="0" />
</iframe>