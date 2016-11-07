Generating a New Command
========================

.. caution::

    If your application is based on Symfony 3, replace ``php app/console`` by
    ``php bin/console`` before executing any of the console commands included
    in this article.

Usage
-----

The ``generate:command`` command generates a new Command class for the given
console command.

By default the command is run in the interactive mode and asks questions to
determine the bundle and the command name:

.. code-block:: terminal

    $ php app/console generate:command

To deactivate the interactive mode, use the ``--no-interaction`` option or its
alias ``-n`` but don't forget to pass the required argument:

.. code-block:: terminal

    $ php app/console generate:command -n AcmeBlog acme:blog:publish-posts

The "Bundle" suffix is added automatically since version 3.2.

Available Arguments
-------------------

* ``bundle``: The name of the bundle where the command class is generated.
* ``name``: The name of the command as you type it in the console.
