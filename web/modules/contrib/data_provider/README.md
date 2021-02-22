Data Provider
===========

The data provider module provides a way to encapsulate an internal/external resource that can be used in multiple areas of a web application without rewriting logic that is found usually in the frontend application. Think of the data provider as a middleware between fetched data, and the presentation layer.

It comes with an HTTP Request fetcher which allows for consuming an internal/external API endpoint. The fetched data is then moved to the transformers stage, which can be a series of plugins that parses and manipulate the fetched data.

Both the fetcher, and transformer implementations are built using a pluggable architecture. Which allows developers to write custom code to transform/or fetch data from another source. The following plugins are provided with this module:

**Fetcher:**

  - HTTP Request

**Transformer:**

  - JSON Decode
  - Array Value Formatter

Data provider resources are cacheable, which normally is not the case when working with traditional HTTP requests, unless using another caching mechanism. Each resource also adheres to the permission system to allow certain roles access.

Installation
------------

* Normal module installation procedure. See
  https://www.drupal.org/documentation/install/modules-themes/modules-8

Initial Setup
------------

- Navigate to `/admin/config/data-provider/resource`. Here you'll be able to see all data provider resources.

- Click the `Add Resource` in the left corner.

- Input the `Resource Label` and select a `Resource Fetcher`. Now you'll be presented with more options.

  - Select if the URL is internal or external, then input the URL to the API endpoint.

  - Request options, allow you to tweak how the system is making the HTTP request. Change to your desire.

  - Next, `Resource Transformer` this section will list out all the transformers that will be manipulating the fetched data. If you're dealing with a traditional API endpoint, you'll need to select `JSON Decode` as the first transformer.

      - The `Array Value Formatter` transformer plugin can be used to format values found in the decoded JSON object. Input the required dot notation and select a given formatter.

  - Now, `Resource Caching` can be set based on your desired outcome. Caching is disabled by default and needs to be enabled to take advantage of this feature.

- Finally, you can click `Save`.

- Now you can access the data provider resource API endpoint, which has the transformers applied. You'll need to navigate to `/data-provider/api/resource/[RESOURCE_ID]`. Replace `[RESOURCE_ID]` with the data provider resource ID (it's the machine name which was generated from the resource label).

  - Use the Data Provider endpoint in your React, or Vue application.
