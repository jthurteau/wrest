# saf
Structured Authoring Foundation

A framework agnostic foundation library for Structured Authoring Development.

SAF provides wrapper funcionality for:

- Some of PHP's native function shortcomings, e.g. Array functions
- Framework agnostic and chainable application kickstarting
- Debugging
- Hooks for RESTFUL API development and consumption

SAF introduces no global scope pollution and aims to provide mechanims to allow the transition from reliance of such practices. While it allows complete flexibility in coding practices, the recommended mechanism for "kickstart" is as follows:

SAF is not an application framework, though it can perform that role or work in tandem with one or more frameworks. Rather than focusing on monolithic application design, SAF adopts a middleware/pipeline approach. The traditional monolithic approach can be supported, but shoe horns many of the following mechanism into a very specific mode of operation.

SAF works with a software "project", a bundle of one or more units of code. These might be defined as applications, modules, or some other core functionality. The project is leveraged through "transactions" to make the code useful work.

Every transaction enters through a "gateway script" regardless of the PHP_SAPI (e.g. a request coming in from the web server, or a commandline interaction). For web based PHP transactions the gateway is a PHP file in a public path on the server.

In trivial cases, the gateway script may perform all of the following functions of kickstart, but most often it will minimally delegate to another script outside of the server's public path.

The functions of kickstart are:

- Rooting, anchoring the transaction in the underlying host environment and normalizing the environment (e.g. paths in the filesystem, or start time)
- Planning, establishing what component(s) of the project to use
- Agency, identifying an "instance" that will provide the basis for configuration
- Tethering, handing off the transaction as input to perform the core work and returning output to conclude the transaction

The functions of kickstart happen across script files (PHP files) and execution scopes which can be tailored to the project's needs.

## Gateway Scripts

Assuming the gateway doesn't handle all of the work itself, it minimally needs to know what path the scripts that follow are at. It is recommended that the gateway not change the current path (chdir).

It is recommended that the gateway delegate the bulk of kickstart operations to scripts in a non-public path, centralized as best suited to the project.

The gateway may delegate rooting tasks to a localization script (prefered), or itself perform some rooting operations as described for localization scripts below. If the gateway delegates rooting tasks it should assume the Rooting Pattern detailed below.

The gateway and merge and manage canister data in whatever manner is most suitable for the project. If any canister data is provided and deemed appropriate it should be padded using the Tethering Pattern detailed below.

The primary recommendation for a gateway script is to keep its work minimal and tether forward as much work as possible to later scripts. Projects may build/manage gateway scripts, or focus on making them as universally static and portable as possible.

## Gateway Scope

The gateway should also create a scope. The recommended scope is a closure (an anonymous function, called immediately following declaration). It should close all operations over try/catch constructs with a bare-minimum handling for any untrapped exceptions. The behavior of handling may be influenced by rooting, and it may tether forward.

SAF aims for Zero Pollution, so gateways should also avoid defining any constants in the global scope, but may optionally use a namespace and define constants in that namespace. SAF makes a few assertions about how such a namespace should not be used during kickstart and no assertions on how may may/should/shouldn't be used beyond the scope of kickstart (e.g. by code that is native to the project).

## Localization Scripts

While optional, localization scripts facilitate a low-level option for rooting of the PHP envorinment. SAF's provided gateway script looks for an optional "local-dev" localization script. As the name implies, it is for facilitating normalization of local-development environments. As such a sample of the local-dev script is included, but the sample file is not leveraged "out ot the box".

Projects may build/manage other localizations scripts as appropriate.

Localization scripts should follow the Rooting Pattern outlined below.

Localizations scripts may introduce sideeffects, especially at it relates to the PHP environment (e.g. ini_set). Such operations should happen as immediately after the start of the transaction as possible, i.e. localization should be the first thing gateway scripts do.

Other scripts following the gateway should not perform localization operations.

## Rooting Pattern

The rooting pattern provides a way to gather optional data. Rooting should not throw exceptions. Rooting should result in an array of data. All other values, including callables, returned from rooting are ignored and null is assumed.

Rooting happens when:

- one (outer) script with it's own scope (closure) includes another, and 
- the outer script verifies the other script exists and is reabable first, and
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

PUBLIC_PATH
INSTALL_PATH
LOCALIZE_TOKEN
ENABLE_LOCAL_DEV
FOUNDATION_PATH
**START_TIME