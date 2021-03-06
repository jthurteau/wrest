# saf
Structured Authoring Foundation

A framework agnostic foundation library for Structured Authoring Development.

SAF provides wrapper funcionality for:

- Flexible, framework agnostic application kickstarting (bootstrapping)
- Development tools like: Testing, Profiling, Debugging, Insulation, Backending, etc.
- Hooks for RESTFUL API development and consumption
- Some of PHP's native function shortcomings, e.g. Array functions

SAF introduces minimal global scope pollution and aims to provide mechanims to allow the transition from reliance of such practices. When used as a "bootstrap", it allows a high degree of flexibility in coding practices. Its components are designed to be leveraged whether it is employed for bootstrapping or not.

The bootstrapping facility for SAF is called "kickstart" because it is used to encapsulate existing framework bootstrapping processes. This allows projects to adroitly employ mutiple applications managed by multiple (including no) frameworks running in parallel.

Kickstart models an "application's" excecution lifcycle as a "transaction". The web server hands off the transaction to a "gateway script", which identifies what application to execute and provides a baseline response if the application fails.

Most frameworks provide a similar mechanism called "bootstrap" process that include some functions of kickstart, and fill a similar role in executive function. Since SAF is framework agnostic, it provides Kickstart as an optional intermediary process that dovetails into (or replaces) existing framework bootstraps. It is designed to be flexible such that SAF can manage the entire gateway-to-shutdown application execution lifcycle, or negotiate any portion of it.

## Core Concepts ##

SAF works with a software "project", a bundle of one or more units of code that can be compined in different ways to create one or more applications. The project is leveraged through "transactions" to make the code useful work.

Every transaction enters through a "gateway script" regardless of the PHP_SAPI (e.g. a request coming in through a web server, or a commandline interaction). For web based PHP transactions the gateway is a PHP file in a public path on the server.

SAF supports single gateway models (e.g. using URL Rewrites to tunnel all requests through one gateway), and multi-gateway models (e.g. using Multiviews to negotiate the mixture of static and dynamic content).

SAF's Kickstart follows a general MVC (Model-View-Controller) like design pattern that leverages IoC. The basic components of SAF that map to "MVC" are:

- Gateways and Tethers: Route and manage requests like a "Controller", providing the primary mechanisms for flow control. "Gateways" are the primary entry-and/or-exit points to applications, and they invoke "Tethers" to modularize flow control internally.
- Workflows and Roots: SAF encapsulates all application execution and environment in the "Model". However you define the core "application(s)", SAF abstracts it as "Workflow". Application environment is abstracted as "Roots" which read or set environment; normalizing, or adapting it for the application(s). Roots also "insulate" the application from the environment which facilites testing and uplifting legacy code.
- Vents and Meditations: Provide mechanisms for any output not delegated to or handled by the applicaiton. "Vents" can wrap APIs, catch exceptions, and generally suppliment or manage the "View" of applications. While Vents are outward focused, "Meditations" provide a similar facility for more inward I/O management.

 <!-- of MiddlewareThe key work of any transaction ultimately gets delegated to some Application, Module, Service, or other similiar "Model" agent. SAF works well with code designed for Middleware frameworks, and "callables" in general. It also provides tools to "wetware" applications through APIs, framework bootstrapping, backending, etc. This can be Middleware Modules, bootstrapped framwork applications, out-sourced services, or anything that performs work immediately upon invocation and returns its results.-->

SAF supports any "encapsulated executive code" as the Model, and it is the objects' invoked methods that constitute the Model from SAF's workflow oriented perspective. Aside from these traditional staples of program design, "Roots" provide a special facility in the model to encap

A strong gateway completely encapulates the server environment from the application it delegates to, and traps any application errors. SAF provides many optional components towards these goals. These insulating tools have a wide variety of appliations, from testing to cloud deployment.

Using "rooting" as Configuration Providers is one way SAF can helps insulate the application from environment. Through the kickstart process, relevant environment details are captured and passed on in towards building the application's "container" or "configuration"

SAF's flexible "tethering" method for routing from the gateway to any bootstrapping that needs to happen keeps the global scope clean and allows multiple applications leveraging different frameworks to dispatch each other over the course of a single transaction.

## PHP's Split Pipe Model

Like the Unix scripting model, PHP has multiple routes for output:

- PHP scripts "return" values, generally this is information not directly sent to an end-user
- they also "emit" standard output that is traditionally directed at a web browser, but can be redirected in a variety of ways
- additional streams can be opened to files, and other destinations, including the input to other scripts

## Invokables

All scripts are "invokables", what differentiates scripts is their utility. SAF employes an seconary file extension(e.g. #TODO) as a recommendation to clarify what a given file is intended for (and to disuade direct invocation).

Invokables are "executive", "declarative", or "encapsulating" depending on whether, respectively, they:
- take action along the transaction's execution lifecycle, 
- set environmental state, or 
- return a value.

Invokables can be any combination of these classifications depending on what role the serve. Ideally scripts should only perform one or two.

Executive scripts influence the flow of the application's execution directly, they react to the current local state and decide what other scripts to invoke and what to return (or emit).

Declarative scripts change the execution environment of the current transaction. They "create" data some of which is inherently bound to the execution environment in a way that is persistent (though sometimes un-doable) and globally accessible. This includes traditional declarative scripts, like class definition files. Even declarations in namespaces introduce a small footprint into the global scope, and in PHP many named declarations are not un-doable: constants, functions, classes, traits, interfaces, etc.

Changes to the execution environment are any changes specific the current transaction, that go away as soon as execution ends. As such outputing to a file or database would not be "execution environment" but "environment".

Encapsulating scripts also "create" data, but in a way that does not "bind to the environment". Encapsulating scripts do something in a local scope, and may return it, but the results must be persisted by the invoking script. This construct allows local values, a return value, and anonymous functions and classes*. 

*(technically anonymous classes are bound to the execution environment in PHP, but in a way that is suffciently "cloaked")

## Gateways, Kickstart, Bootstrapping ##

https://lucid.app/lucidspark/invitations/accept/c2332b74-627d-4732-997b-43ee541f0bc1

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

Gateways may tether to more than one other script, sequentially or conditionally. Gateways are not themselves tethers, gateways cannot "tether" another gateway, but they may invoke them. 

Gateways are executive files, they take action upon invocation (as do Roots). This is in contrast to Tethers which declare and return an anonymous closure, but don't otherwise execute other actions.

While gateways ideally don't (leak) output, in the case of a sufficiently critial error or exception they may use the ""Strand"" pattern to "vent" the error.

### Gateway Scope

The gateway should also create a scope. The recommended scope is a closure (an anonymous function, called immediately following declaration). It should close all internal operations over try/catch constructs with a bare-minimum handling for any untrapped critical errors and exceptions. The behavior of handling may be influenced by rooting, and it may tether forward handling.

The data passed forward through tethering is (typically) passed by reference, so it often serves as an additional resource source for shutdown operations, or venting any critical errors or exceptions when execution returns to the gateway.

In a "normal" transaction, execution returns back through the gateway, and the gateway terminates naturally (implicit void return). Gateways only return/exit/die as needed for their function. Gateways may interpret the return of a tether as part of their operation. 

SAF aims for Zero Pollution, so gateways should also avoid defining any constants in the global scope, but may optionally use a namespace and define constants in that namespace. SAF makes a few assertions about how such a namespace should not be used during kickstart and no assertions on how may may/should/shouldn't be used beyond the scope of kickstart (e.g. by code that is native to the project).

Typical data management assumes that the Gateway "creates" its own canister of data based on information from rooting, It should

### Recommended Gateway Footprint

The implementation of gateways is entirely up to the developer. The following are purely recommendations as a basis of good design:

- a flexible gateway needs to be able to tether an arbitrary file in the public path to an arbitrary file in a directory outside of the public path. The directory outside of the public path is refered to as the "install path" and is also often the "project root" when a more complicated multi-project scenario is not in play. The common modern practice is for the public path to be a subdirectory of the install path, so mapping is as simple as going up the directory tree one level. If the mapping is always straightforward and predictable, it can be hard coded. If it is parametarized, it is recommended to be the first parameter (even if it is the one least likely to varry). Auto-detection and reading from the environment are acceptable, but not fitting in best practice.

- in addition to the path for the non-public directory holding the tether, the other component of any tether is the filename. It isn't assumed how this might varry. An implementation where there are multiple tethers per install path is just as valid as multiple install paths with a uniform tether, or different tether setups. The tether file name and path may be a single or seprate parameter depending on what makes the most sense.

- in addition to paths related to gateway entry, there is another common case that the gateway needs to handle and that is critical error display (venting). This can be as simple as a 30\[2|3|7] redirect, or serving a static file. It is also possible to serve a tratitional PHP template style file. It is common, but not assumed that the file(s) needed for this are in the same install path. A third parameter may be needed for pathing to a script/tether for venting.

- gateways may allow soft-wiring of root/canister data to pass forward, when this is parametarized it should be the last parameter.

- gateways may have any return value. It is not recommended to use them as a tether script even when they happen conform to the single array param, callable return footprint. Tethers should not vet.

## Pylon Scipts

One special case of the gateway pattern can occur when you want multiple URIs at the root of your public path to gateway through index.php. Consider the following examples:

\[uri]/ = index.php = controller/module "main"
\[uri]/(modrewriterule) = index.php = controller/module + route path "main/(modrewriterule)"
\[uri]/mobile(/\*) = modile.php via multiviews (includes) index.php = controller/module "main/mobile(/\*)"
\[uri]/admin(/\*) = admin.php (includes) login.php = framework eventually maps this to the same controller/module as "main(/\*)"
\[uri]/subdirectory(/\*) = index.php (somewhere other than docroot) = controller/module "main"

In the first example, index.php is a gateway that maps the root of the public path to the root of an application's routes. Assuming a typical routing scheme the application might fairly easily define exceptions. It is important to note that if the public path does not match the document root of the server, anything attempting to generate non-relative URIs to various resources in the public path would need to know the mapping of document root to public path. The application would also need to know this discrepency in mapping. The web server also has to be configured to know what URL(s) to map to index.php. Assuming a typical setup, a PHP aware server would recognise the .php file extention and hand off such requests to a PHP interpreter. It may map index.php as an index file so public_path/index.php and public_path/ both resolve to index.php. Other URIs would require speific web server configuration.

In the second example a typical mod-rewrite rule is used to map any /X URI to index.php and most application routers would be able to support internal configuration to map X to various routes. The web server has to be configured to properly handle requests for public path resources (like images and css) that are not handled by the PHP application. This approach alone does not solve public path to document root mapping needs. 

The third example uses a different gateway script and multi-views (an Apache feature) to "pylon" all URIs under /mobile through the gateway at index.php. Some frameworks support URI piping (or similar features) to then map that URI though middleware or some special handler to some other route, or attach special signifigance to the extra /X portion of the path. SAF's pylon scripts can use the Saf/Resolver to help re-write the URI/routing for most frameworks.

The fourth example illustrates an example of using gateways and tethers as "middleware". Suppose there is a case where authentication code doesn't integrate well with an application or the framework it is implemented in. "admin.php" may be a Gateway or Pylon (a public path script), there are a variety of reasons it might be best implemented as a Pylon, but URI rewriting is one case. SAF can route unauthenticated requests through one application/middleware to handle authentication and pass requests through to the "main" appliation once an authentication session has been established.

The final exmaple explicitly addresses the case of public path to document root mapping. A lot of applications are written assuming the will be served from the root of the web server's hostname with varying degrees of support for being served from a subdirectory. SAF's Resolver provides mechanisms for mapping multiple public paths to different relative paths. This makes it very easy to change what URI an appliation is served from on the fly by configuration.

Pylon Scripts allow for handling special cases such as URI Rewriting. While Root Scripts insulate the appliation from environment by encapsulating it (generally, environment should only be read through root scripts), Pylon Scripts allow writing environment (which would later be read by root scripts) because they are always evaluated before the gateway. Pylon scripts replace the entry (i.e. directly triggered from "outside") function of gateway scripts when they do this. Pylons may create a closure, but are not required to when they declare no variables or functions. Pylons may not invoke root scripts, and should primarily write to the enrionment, not read. Pylons should not execute any statements after invoking the gateway, they should not tether, and generally should not return.

URI Rewriting is the most common case for Pylon Scripts, but they are a general mechanism for environment normalization and localization.

Localizations are a special case that acts a both a Root Script and a Pylon Script. Rather than invoking the gateway, it is passed a the paramater's to the gateway's closure call. e.g. function($root=[]){})(...include('pylon.php'));

## Localization Scripts

While optional, localization scripts facilitate a low-level option for rooting and normalization of the PHP envorinment. SAF's provided gateway script looks for an optional "local-dev" localization script. As the name implies, it is for facilitating normalization of local-development environments. As such, a sample of the local-dev script is included, but the sample file is not leveraged "out ot the box".

Projects may build/manage localizations scripts as appropriate.

Localization scripts should follow the Rooting Pattern outlined below, with the exception that it is understood they may introduce sideffects into the local environment (e.g. ini_set). Such operations should happen as immediately after the start of the transaction as possible, i.e. localization should be the first thing gateway scripts do.

Other scripts following the gateway should avoid localization operations.

Since the localization script is also a "rooting" script, it acts as a seed for data tetheref forward. It is a general practice that earlier rooted data is favored over later rooted data.

# SAF Patterns

SAF leverages a number of patterns for clean and consistent mechanisms. These include some conventsions:

"Invoking" generally means include\[_once] or require\[_once] depending on desired behavior.

"Verifying" generally means taking reasonable precautions with a file or its contents before including.

These two guildelines largely depend on intent and impact. 

If files are considered optional, they should be included. If files are considered, they should be required. The choice to invoke files with the "_once" variant of include/require involves similar invormed judgement about optimization and behavior (i.e. use them if you're certain).

Verification similarly depends on adhering to expectations, it is a recommendation that aims to prevent uncaught exceptions and fatal errors when they are not appropriate. Should an applicaiton fail when caching isn't working? The answer is up to the programmer, but if it is considered "optional" it should not.

## Rooting Pattern

The rooting pattern provides a way to gather optional data. Rooting should not throw exceptions. Rooting should result in an array of data. All other values, including callables, returned from rooting are ignored and null is assumed.

Rooting happens when:

- one (outer) script with it's own scope (closure) invokes another, and 
- the outer script "verifies" the other script first, and
- the outer script gets an array from the returned value of the include, and
- if any of the preconditions fail the outer script treats the result as an empty array (no data, not an error), and
- the called script does not leak/vent (produce output)

It is recommended that "root scripts" make no assumptions about variables provided in the local scope by the outer script. There is no mechanism for passing data from the outer script, the root script should only read from the "environment". The outerscript may buffer its local scope from the root script if appropriate.

Root scripts should be freely allowed to establish local variables as needed. Namespaces are not recommended for root scripts, root scripts should not set constants. 

While root scripts can't return a closure, the array returned may include closures.

Root scripts may close over their contents to ensure a separate scope when polution is a concern. Distributed/reused roots, for example should, single-use roots tightly coupled with a specific outer script don't need to.

## Tethering Pattern

The gateway and any other scripts between it and the core code of the project are connected by tethering.

Tethering happens when:

- one (outer) script invokes another, and
- the outer script "verifies" the called script first, and
- the outer script gets a callable from the returned value of the include, and
- if the outer script has data to pass forward it invokes the callable with that data as a single array, and
- if the return value isn't callable the transaction is considered "handled", and
- if the invocation doesn't throw an exception the transaction is considered "handled", and
- the called script does not leak/vent (produce output)

Tethered scripts should declare their own closure and return it for the outer script to call. Passing to the closure is the only mechanism for passing data from the outer script. The closure must accept one array of data. It may accept additional optional parameters (not recommended).

Tethered scripts should be freely allowed to establish local variables. Tethered scripts may declare namespaces and should not declare contants not in a namespace. Tethered scripts should access the limited about of global environment (e.g. the file system) needed to do their work and leverage data passed in as much as possible.

Tethered scripts may throw errors or exceptions and should not generate output.

Tethered scripts may invoke a gateway.

## Planning and Agency

The scripts tethering the gateway to core project code are left largely up to the needs of a project. They may be original gateway script(s) provided by a framework.

SAF provides an additional optional middle layer for binding gateways to configuration to facilitate the many ways frameworks and applications might need to be prepared prior to their internal "bootstap" process.

The included gateway tethers an "instance script". An "instance" might be any conbination of identifying facets that inform the building of configuration (or the applications "container"). The instance script decides "what to run" or "who is responding" and tethers to a "foundation script". The foundation script embodies the implementation specific method of bootstrapping the chosen instance and decides "how to run" it.

The instance script may be distrubuted with your project, or built/managed. Similarly the foundation script (when applicable) would be distributed or managed by the dependencies of your project (e.g. a framework).

By implementing tethering, SAF provides a simple flexible way to use out-of-the-box bootstrapping or drive more complex behaviors.

Some frameworks may be designed with the assumption they do all the lifting, others are more pluggable. SAF supports both models and facilitates migration from the former, more monolithic approach to the latter more modern approach.

The provided instance script injects no instance identity into the canister it tethers to the foundation script, but if the "mainScript" key in the canister had been set prior, it gets passed along, as is to the foundation script. The instance script does attempt to make educated guesses about where to find SAF's kickstart script using the canister's "appliationRoot" and "foundationPath" keys if present, or the instance scripts own path. It then uses the kickstart script "kick.php" as the foundation script.

SAF's foundation script simply checks the canister for a "mainScript", which it will use to try and identify an instance of the application to run. Using that and the rest of the data in the canister, it then tries to auto-detect what framework the "mainScript" was written for and if it is a supported framework SAF will handle any bootstrapping that framework's default gateway script would have originally performed.

## Workflow Pattern

The workflow pattern is fairly vague, any invokable can be a workflow.

## Cache and Short-Circuiting

At any point in the rooting/tethering process, including a localization pylon, it is possible to:

- Cache the current state of a canister
- Short-Circuit the canister (aka fast-forwarding) to another gateway-tether



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
BASE_URI - root relative or absolute URI that maps to PUBLIC_PATH, useful for generating non-relative links. This can be provided, or auto-calculated.
APPLICATION_PATH - path to the root of a signified application, mapping varies by project structure
APPLICATION_ROOT - path to the root of managed applications, typically /opt/application, /opt, /var/www/application, /var/www, etc.
VENDOR_PATH - path to the root of managed dependencies, typically INSTALL_PATH/vendor, but paths outside of INSTALL_PATH are suppored.

RESOVLER_PYLON - a portion of URI_MIRROR, aka $_SERVER\['PHP_SELF'] to match for BASE_URI calculation and Resolver routing (auto-piping). Matching RESOLVER_PYLON helps map PUBLIC_PATH to the BASE, marking the position in the served URI to where BASE_URI ends. The rest of the URI, including the match an everything else in the path, is the route for the app. From the application's perspective everything before RESOVLER_PYLON is chopped of from the URI as if PUBLIC_PATH were directly under the root of the URI path. Processing for RESOVLER_PYLON will try to automatically negotiate the cases where the ".php" suffix is needed, and where it is not.

RESOLVER_FORWARD - On occasion, you may need to handle request edge cases at a completely different route, RESOLVER_FORWARD is a tool that can be used with Resolver and RESOLVER_PYLON to handle a lot of such special cases. It is also a useful combination with more rigid routing schemes. After RESOLVER_PYLON is matched to the URI, RESOLVER_FORWARD replaces that match in the resulting route. RESOLVER_FORWARD often includes the value in RESOLVER_FORWARD, e.g. 
login -> default/index/login, or 
login -> index/login/form, or
login -> login/sso

RESOLVER_FORWARD does not have to be in RESOLVER_PYLON, however. It is a straightforward find-replace operation, so:

login -> sso/auth

also works. 

Many uses for RESOLVER_FORWARD are targeted at older frameworks with more rigid routing schemes. If your routing infrastructure is flexible enough it is often more straightforward to implement the routes natively. RESOLVER_FORWARD can be useful as a pre-dispatch forwarding mechanism, but it is a mechanism more universally handled by modern frameworks. In contrast, many frameworks still rely on third-party extensions to handle the usecase for RESOLVER_PYLON.

## Other Environment Values (less common, or supported in a deprecated manner)

URI_MIRROR - a value that can be provided in cases where PHP_SELF and SCRIPT_NAME are unavailable,inaccurate, or you don't want to access $_SERVER. It should be the gateway's \__FILE__ path relative to the web servers Document Root. Primarily used to calculate BASE_URI. (See BASE_URI and RESOLVER_ANCHOR)
URI_MIRROR_SOURCE - a key in $_SERVER to look for the value that would normally be provided in $_SERVER\['PHP_SELF']

LIBRARY_PATH - deprecated path to root of managed libraries, use VENDOR_PATH when possible. LIBRARY_PATH is often symlinked directly to the relevant portion of a complementary VENDOR_PATH (e.g. LIBRARY_PATH/Zend = VENDOR_PATH/Zend/library/Zend )
APPLICATION_ID
APPLICATION_HANDLE
APPLICATION_NAME

LOCALIZE_TOKEN -
ENABLE_LOCAL_DEV
CANISTER_FIFO - determines if kickstart prefers earlier set canister values (defaults to true)

APPLICATION_LEGACY_VECTOR - short-circuit the gateway to kickstart a pre PHP7 SAF app (value specifies a tether script)
LIBRARY_PATH - an older form of VENDOR_PATH where each directory is source code (e.g. LIBRARY_PATH/library_name/ may simply be a symlink to VENDOR_PATH/project_name/src). Useful for including external code prior to modern autoloader methods.

# PHAgent

Phagent or PHP-Agent, is a IoC programming pattern involving a canister (array) that includes self modifying callables (functions built closing around an array reference). The Phagent is passed along using tethering, and it can tether itself when IoC is desireable.

Phagents wrap core php functions or control structures that might normally trigger a fatal error and attempt to mitigate them with a thrown exeception/error instead. 

Phagents can also be cached to create a sort of application state snapshot, which in combination with a stack-trace can be used to re-create that appliation state.

PHAgents are designed to easily hybernate or mementize as arrays, and should generally be interchangable with arrays. They implment the ArrayAccess pattern and include methods to convert themselves from arrays to ArrayAccess objects as needed.

# Recommended Exception Codes

Code 127, used for errors loading scripts (e.g. file not found), sets a previous Exception where the message is the fine in question.