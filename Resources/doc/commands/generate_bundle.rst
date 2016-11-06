Generating a New Bundle Skeleton
================================

.. caution::

    If your application is based on Symfony 3, replace ``php app/console`` by
    ``php bin/console`` before executing any of the console commands included
    in this article.

Usage
-----

The ``generate:bundle`` generates a new bundle structure and automatically
activates it in the application.

By default the command is run in the interactive mode and asks questions to
determine the bundle name, location, configuration format and default
structure:

.. code-block:: terminal

    $ php app/console generate:bundle

To deactivate the interactive mode, use the `--no-interaction` option or its
alias `-n` but don't forget to pass the required argument:

.. code-block:: terminal

    $ php app/console generate:bundle -n Acme/Bundle/BlogBundle

Available Arguments
-------------------

``namespace``

    .. versionadded:: 3.2
        The argument was introduced in 3.2, use the option in prior versions.

    The namespace of the bundle to create. The namespace should begin with
    a "vendor" name like your company name, your project name, or your client
    name, followed by one or more optional category sub-namespaces, and it
    should end with the bundle name itself (which must have "Bundle" as a
    suffix or it will be one automatically):

    .. code-block:: terminal

        $ php app/console generate:bundle -n Acme/Bundle/Blog --shared

    will generate the bundle ``./src/Acme/Bundle/BlogBundle/AcmeBlogBundle.php``.
    The ``--shared`` option defaults configuration format to ``xml``, and create
    a directory for dependency injections.

    You can also create a simple application bundle using:

    .. code-block:: terminal

        $ php app/console generate:bundle -n Blog

    will generate the bundle ``./src/BlogBundle/BlogBundle.php``.


Available Options
-----------------

``--shared``
    Provide this option if you are creating a bundle that will be shared across
    several of your applications or if you are developing a third-party bundle.
    Don't set this option if you are developing a bundle that will be used
    solely in your application (e.g. ``AppBundle``).

``--namespace``
    .. caution::
        This option has been deprecated in version 3.2, and will be removed in 4.0.
        Pass it as an argument instead.

    The namespace of the bundle to create. The namespace should begin with
    a "vendor" name like your company name, your project name, or your client
    name, followed by one or more optional category sub-namespaces, and it
    should end with the bundle name itself (which must have Bundle as a suffix):

    .. code-block:: terminal

        $ php app/console generate:bundle --namespace=Acme/Bundle/BlogBundle

``--bundle-name``
    The optional bundle name. It must be a string ending with the ``Bundle``
    suffix (or it will be added automatically from version 3.2):

    .. code-block:: terminal

        $ php app/console generate:bundle --bundle-name=AcmeBlogBundle

``--dir``
    The directory in which to store the bundle. By convention, the command
    detects and uses the application's ``src/`` folder:

    .. code-block:: terminal

        $ php app/console generate:bundle Blog --dir=/var/www/myproject/src

``--format``
    **allowed values**: ``annotation|php|yml|xml`` **default**: ``annotation`` or ``xml``

    Determine the format to use for the generated configuration files (like
    routing). By default, the command uses the ``annotation`` format or ``xml``
    when ``--shared`` option is used (choosing the ``annotation`` format expects
    the `SensioFrameworkExtraBundle`_ to be installed):

    .. code-block:: terminal

        $ php app/console generate:bundle Blog --format=yml

.. _`SensioFrameworkExtraBundle`: http://symfony.com/doc/master/bundles/SensioFrameworkExtraBundle/index.html
