# saf
Structured Authoring Foundation

A framework agnostic foundation library for Structured Authoring Development.

SAF provides wrapper funcionality for:


- Framework agnostic and chainable application kickstarting
- Debugging and Insulation
- Hooks for RESTFUL API development and consumption
- Some of PHP's native function shortcomings, e.g. Array functions

SAF introduces no global scope pollution and aims to provide mechanims to allow the transition from reliance of such practices. While it allows complete flexibility in coding practices, the recommended mechanism for "kickstart" is as follows:

Encapsulate entry into the "application" as a "transaction". The web server hands off the transaction to a "gateway script", which identifies what to execute and provides a baseline response if the application fails.

Most frameworks provide a "bootstrap" process that may include some functions of the gateway, and fill a similar role in executive function. Since SAF is framework agnostic, it provides an optional intermediary "kickstart" process. It is designed to be flexible such that SAF can manage the entire gateway-to-bootstrap process, or negotiate any portions of it.

## Core Concepts ##

SAF works with a software "project", a bundle of one or more units of code that can be compined in different ways to create one or more applications. The project is leveraged through "transactions" to make the code useful work.

Every transaction enters through a "gateway script" regardless of the PHP_SAPI (e.g. a request coming in through a web server, or a commandline interaction). For web based PHP transactions the gateway is a PHP file in a public path on the server.

SAF supports single gateway models (e.g. using URL Rewrites to tunnel all requests through one gateway), and multi-gateway models (e.g. using Multiviews to negotiate the mixture of static and dynamic content).

A strong gateway completely encapulates the server environment from the application it delegates to, and traps any application errors. SAF provides many optional components towards these goals. These insulating tools have a wide variety of appliations, from testing to cloud deployment.

Using "rooting" as Configuration Providers is one way SAF can helps insulate the application from environment. Through the kickstart process, relevant environment details are captured and passed on in towards building the application's "container" or "configuration"

SAF's flexible "tethering" method for routing from the gateway to any bootstrapping that needs to happen keeps the global scope clean and allows multiple applications leveraging different frameworks to dispatch each other over the course of a single transaction.

## Gateways, Kickstart, Bootstrapping ##

In trivial cases, the gateway script may perform all of the following functions of kickstart, but most often it will minimally delegate to another script outside of the server's public path.

The functions of kickstart are:

- Rooting, anchoring the transaction in the underlying host environment and normalizing the environment (e.g. paths in the filesystem, or start time)
- Planning, establishing what component(s) of the project to use
- Agency, identifying an "instance" that will provide the basis for configuration
- Tethering, handing off the transaction as input to perform the core work and returning output to conclude the transaction

The functions of kickstart happen across script files (PHP files) and execution scopes which can be tailored to the project's needs.

## Gateway Pattern

The gateway pattern establishes a controled anchor into the PHP execution, it is designed to work for web transacitons and on the commandline. Gateways abstract a transaction, insulating the transaction from the executing environment, and other transactions.

Gateways conform to the following criteria:

- a script that is directly triggered from "outside" (e.g. web request, commandline or interactive session), and
- has intentionaly minimal direct contact with its executing environment, and
- ideally in no way changes the local executing environment, and
- it delegates reading environment to "rooting" scripts, and
- creates a canister (array) of data, and
- it (typically) delegates execution to one or more "tether" scripts, and
- passes the (or a) canister to tethered scripts, and
- the script closes around all of the above activity, and
- the script executes the closure (it does not just return it), and
- the script handles all exceptions and non-fatal errors, and does not throw

### Gateway Scripts

Assuming the gateway doesn't handle all of the work itself, it minimally needs to know what path the scripts that follow are at. It is recommended that the gateway not change aspects if environment e.g. the current path (chdir).

It is recommended that the gateway delegate the bulk of kickstart operations to scripts in a non-public path, centralized as best suited to the project. Generally, "gateways" should be the only PHP files in a public path.

The gateway may delegate localization tasks to a localization script (prefered), or itself perform some of the operations as described for localization scripts. If the gateway delegates localization tasks it should use the Rooting Pattern detailed below.

When gateways "root" they (may) get an array of data. Gateways decide how to manage that data and what to forward on via tethering. It is generally best practice to do the least possible amount of rooting in the gateway.

The primary recommendation for a gateway script is to keep its work minimal and tether forward as much work as possible to later scripts. Projects may build/manage gateway scripts, or focus on making them as universally static and portable.

Gateways may tether to more than one other script, sequentially or conditionally. Gateways are not themselves tethers, gateways should not directly invoke other gateways.

Gateways are executive files, they take action upon invocation (as do Roots). This is in contrast to Tethers which declare and return an anonymous closure, but don't otherwise execute other actions.

While gateways ideally don't (leak) output, in the case of a sufficiently critial error or exception they may use the ""Strand"" pattern to "vent" the error.

### Gateway Scope

The gateway should also create a scope. The recommended scope is a closure (an anonymous function, called immediately following declaration). It should close all internal operations over try/catch constructs with a bare-minimum handling for any untrapped critical errors and exceptions. The behavior of handling may be influenced by rooting, and it may tether forward handling.

The data passed forward through tethering is (typically) passed by reference, so it often serves as an additional resource source for shutdown operations, or venting any critical errors or exceptions when execution returns to the gateway.

In a "normal" transaction, execution returns back through the gateway, and the gateway terminates naturally (implicit void return). Gateways only return/exit/die as needed for their function. Gateways may interpret the return of a tether as part of their operation. 

SAF aims for Zero Pollution, so gateways should also avoid defining any constants in the global scope, but may optionally use a namespace and define constants in that namespace. SAF makes a few assertions about how such a namespace should not be used during kickstart and no assertions on how may may/should/shouldn't be used beyond the scope of kickstart (e.g. by code that is native to the project).

Typical data management assumes that the Gateway "creates" its own canister of data based on information from rooting, It should

## Localization Scripts

While optional, localization scripts facilitate a low-level option for rooting and normalization of the PHP envorinment. SAF's provided gateway script looks for an optional "local-dev" localization script. As the name implies, it is for facilitating normalization of local-development environments. As such, a sample of the local-dev script is included, but the sample file is not leveraged "out ot the box".

Projects may build/manage localizations scripts as appropriate.

Localization scripts should follow the Rooting Pattern outlined below, with the exception that it is understood they may introduce sideffects into the local environment (e.g. ini_set). Such operations should happen as immediately after the start of the transaction as possible, i.e. localization should be the first thing gateway scripts do.

Other scripts following the gateway should avoid localization operations.

Since the localization script is also a "rooting" script, it acts as a seed for data tetheref forward. It is a general practice that earlier rooted data is favored over later rooted data.

# SAF Patterns

SAF leverages a number of patterns for clean and consistent mechanisms. These include some conventsions:

"Including" generally means include\[_once] or require\[_once] depending on desired behavior.

"Verifying" generally means taking reasonable precautions with a file or its contents before including.

These two guildelines largely depend on intent and impact. 

If files are considered optional, they should be included. If files are considered, they should be required. The choice to invoke files with the "_once" variant of include/require involves similar invormed judgement about optimization and behavior (i.e. use them if you're certain).

Verification similarly depends on adhering to expectations, it is a recommendation that aims to prevent uncaught exceptions and fatal errors when they are not appropriate. Should an applicaiton fail when caching isn't working? The answer is up to the programmer, but if it is considered "optional" it should not.

## Rooting Pattern

The rooting pattern provides a way to gather optional data. Rooting should not throw exceptions. Rooting should result in an array of data. All other values, including callables, returned from rooting are ignored and null is assumed.

Rooting happens when:

- one (outer) script with it's own scope (closure) includes another, and 
- the outer script "verifies" the other script first, and
- the outer script gets an array from the returned value of the include, and
- if any of the preconditions fail the outer script treats the result as an empty array (no data, not an error)

It is recommended that "root scripts" make no assumptions about variables provided in the local scope by the outer script. There is no mechanism for passing data from the outer script, the root script should only read from the "environment". The outerscript may buffer its local scope from the root script if appropriate.

Root scripts should be freely allowed to establish local variables as needed. Namespaces are not recommended for root scripts, root scripts should not set constants. 

While root scripts can't return a closure, the array returned may include closures.

## Tethering Pattern

The gateway and any other scripts between it and the core code of the project are connected by tethering.

Tethering happens when:

- one (outer) script includes another, and
- the outer script verifies the other script exists and is reabable first, and
- the outer script gets a callable from the returned value of the include, and
- if the outer script has data to pass forward it invokes the callable with that data as a single array, and
- if the return value isn't callable the transaction is considered "handled", and
- if the invocation doesn't throw an exception the transaction is considered "handled"

Tethered scripts should declare their own closure and return it for the outer script to call. Passing to the closure is the only mechanism for passing data from the outer script. The closure must accept one array
of data. It may accept additional optional parameters (not recommended).

Tethered scripts should be freely allowed to establish local variables. Tethered scripts may declare namespaces and should not declare contants not in a namespace. Tethered scripts should access the limited about of global environment (e.g. the file system) needed to do their work and leverage data passed in as much as possible.

Tethered scripts may throw exceptions.

## Planning and Agency

The scripts tethering the gateway to core project code are left largely up to the needs of a project. They may be original gateway script(s) provided by a framework.

SAF provides an additional optional middle layer for binding gateways to configuration to facilitate the many ways frameworks and applications might need to be prepared prior to their internal "bootstap" process.

The included gateway tethers an "instance script". An "instance" might be any conbination of identifying facets that inform the building of configuration (or the applications "container"). The instance script decides "what to run" or "who is responding" and tethers to a "foundation script". The foundation script embodies the implementation specific method of bootstrapping the chosen instance and decides "how to run" it.

The instance script may be distrubuted with your project, or built/managed. Similarly the foundation script (when applicable) would be distributed or managed by the dependencies of your project (e.g. a framework).

By implementing tethering, SAF provides a simple flexible way to use out-of-the-box bootstrapping or drive more complex behaviors.

Some frameworks may be designed with the assumption they do all the lifting, others are more pluggable. SAF supports both models and facilitates migration from the former, more monolithic approach to the latter more modern approach.

The provided instance script injects no instance identity into the canister it tethers to the foundation script, but if the "mainScript" key in the canister had been set prior, it gets passed along, as is to the foundation script. The instance script does attempt to make educated guesses about where to find SAF's kickstart script using the canister's "appliationRoot" and "foundationPath" keys if present, or the instance scripts own path. It then uses the kickstart script "kick.php" as the foundation script.

SAF's foundation script simply checks the canister for a "mainScript", which it will use to try and identify an instance of the application to run. Using that and the rest of the data in the canister, it then tries to auto-detect what framework the "mainScript" was written for and if it is a supported framework SAF will handle any bootstrapping that framework's default gateway script would have originally performed.

# Environment and Options

SAF assumes the source names from the environment will be in CONSTANT_FORMAT and recommends storing options into the passed canisters in camelCase (studlyCaps)

## Core Environment Values 
PUBLIC_PATH - path to the root of public files, typically INSTALL_PATH/public
INSTALL_PATH - path to the root of the project, typically the root of the project repo
STORAGE_PATH - default write path for the application, sometimes INSTALL_PATH/data but better practice is outsite of INSTALL_PATH, e.g. somewhere in /var
START_TIME - timestamp of the transaction's start, typically aquired from the web server environment
LOCAL_TIMEZONE - timezone associated with START_TIME and calls to Saf\Time

FOUNDATION_PATH - path to the foundation source code, i.e. where SAF is installed, defaults to VENDOR_PATH/Saf

## Common Environment Values 
BASE_URI - root relative or absolute URI that maps to PUBLIC_PATH
APPLICATION_PATH - path to the root of a signified application, mapping varies by project structure
APPLICATION_ROOT - path to the root of managed applications, typically /opt/application, /opt, /var/www/application, /var/www, etc.
VENDOR_PATH - path to the root of managed dependencies, typically INSTALL_PATH/vendor, but paths outside of INSTALL_PATH are suppored.

## Other Environment Values (less common, or supported in a deprecated manner)

LIBRARY_PATH - deprecated path to root of managed libraries, use VENDOR_PATH when possible. LIBRARY_PATH is often symlinked directly to the relevant portion of a complementary VENDOR_PATH (e.g. LIBRARY_PATH/Zend = VENDOR_PATH/Zend/library/Zend )
APPLICATION_ID
APPLICATION_HANDLE
APPLICATION_NAME

LOCALIZE_TOKEN -
ENABLE_LOCAL_DEV
CANISTER_FIFO - determines if kickstart prefers earlier set canister values (defaults to true)


LIBRARY_PATH - an older form of VENDOR_PATH where each directory is source code (e.g. LIBRARY_PATH/library_name/ may simply be a symlink to VENDOR_PATH/project_name/src). Useful for including external code prior to modern autoloader methods.

# PHAGENT

Phagent or PHP-Agent, is a IoC programming pattern involving a canister (array) that includes self modifying callables (functions that accept an array reference). The Phagent is passed along using tethering, but it can effectively tether itself when IoC is desireable.

Phagents can wrap core php functions or control structures that might normally trigger a fatal error and attempt to mitigate them with a thrown exeception/error instead.

Phagents can also be cached to create a sort of application state snapshot, which in combination with a stack-trace can be used to re-create that appliation state.