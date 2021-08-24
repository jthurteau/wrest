# saf
Structured Authoring Foundation

A framework agnostic foundation library for Structured Authoring Development.

SAF provides wrapper funcionality for:

- Flexible, framework agnostic, encapsulated application kickstarting (bootstrapping)
- Development tools like: Testing, Profiling, Debugging, Insulation, Backending, etc.
- Hooks for RESTFUL API development and consumption
- Powerful relational data management tools
- Some of PHP's native function shortcomings, e.g. Array functions

SAF introduces minimal global scope pollution and aims to provide mechanims to allow the transition from reliance of such practices. When used as a "bootstrap", it allows a high degree of flexibility in coding practices. Its components are designed to be leveraged completely independently.

The bootstrapping facility for SAF is called "[kickstart](https://github.com/jthurteau/saf/wiki/Kickstart)" because it encapsulates existing framework bootstrapping processes. This allows projects to adroitly employ mutiple applications managed by multiple (including no) frameworks running in parallel across enpoints.

## PHP5 Legacy Support

Applications using the original PHP5 version of SAF should checkout against the [php5 branch](https://github.com/jthurteau/saf/tree/php5). The main branch now contains `/legacy` which can be used to help migrate to newer PHP7 objects. This will be deprecated soon.


