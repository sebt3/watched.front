# watched.front
## Overview
[Watched](https://sebt3.github.io/watched/) aim to become a performance analysis and a monitoring tool.
This is the frontend. Allow to view performance metric and monitoring events generated by the backend

## Dependencies
This project is based on the following projects :
* [bootstrap](http://getbootstrap.com)
* [AdminLTE](https://almsaeedstudio.com/preview)
* [d3.js](https://d3js.org/)
* [Font Awesome](http://fontawesome.io)
* [composer](https://getcomposer.org)
* [slim](http://www.slimframework.com)
* [twig](http://twig.sensiolabs.org)


## Other componants
* [watched.back](https://github.com/sebt3/watched.back) Centralize the agents metric and monitor them.
* [watched.agent](https://github.com/sebt3/watched.agent) Collect metrics and monitor services forwarding the information over a REST api.

## Running instruction
    composer install
    php -S 0.0.0.0:8080 -t public/

Complete instructions [here](https://sebt3.github.io/watched/doc/install/#the-frontend).

## Current status
In between a prototype and a full fleged frontend.
At best an alpha status.
