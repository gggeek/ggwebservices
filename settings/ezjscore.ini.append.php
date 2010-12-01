<?php /*

[ezjscServer]
# Permission functions
# Unfortunately we need more fine-grained access control than this, wo we bypass
# ezjscore acl and set up custom access checking within the php code itself
#FunctionList[]=ezp_fetchall
#FunctionList[]=ezp_inspect
#FunctionList[]=ezp_operationall
#FunctionList[]=ezp_viewall

# Example urls to test this server's functions from a browser:
# <root>/ezjscore/call/system::methodList

[ezjscServer_system]
Class=ggwebservicesJSCFunctions

# Policies: see comment above on why it is disabled
###Functions[]=ezp
###PermissionPrFunction=enabled

[ezjscServer_ggwstemplate]
TemplateFunction=true

*/ ?>