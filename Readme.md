# Advanced Custom Fields v 5.0 Recovery Tool from PHP Export #

Use this plugin to import fieldsets from PHP exports. <strong>Do not use this for your workflow of importing and exporting. Use it only as a recovery tool when you lose the original database and XML files.</strong>

1. Include the PHP for the ACF fieldsets generated from the PHP export.
2. Install and activate this plugin.
3. Go to 'Custom Fields' > 'PHP Recovery' page.
4. Check the boxes next to the fieldsets to import and then click the 'Import' button.
5. The fieldset are imported into the database with '(Recovered)' postfixed to the title.
6. Remove (or just comment out) the PHP defined fields as their UID may conflict with the imported fields. Commonly if you leave them in, you will only see the last field on the fieldset edit screen.

## Alternatives ##

https://github.com/iamntz/acf-recovery

## Thanks ##

Thanks to Elliot Condon http://www.elliotcondon.com/ for creating Advanced Custom Fields.

Thanks to Seamus Leahy https://github.com/seamusleahy for creating this project.
