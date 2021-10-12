# OpenEuropa Whitelabel Search

This module offers a searchbox block handled by a plugin.

# Requirements

The following modules must be installed and enabled to use the Search Box block:
 - [Search_api](https://www.drupal.org/project/search_api)
 - [Search_api_autocomplete](https://www.drupal.org/project/search_api_autocomplete)
 - [Views](https://www.drupal.org/docs/8/core/modules/views)
 - [Views UI](https://www.drupal.org/docs/8/core/modules/views-ui)

# Instructions:

The following instructions are meant to help site builders place a block Search Box in the OE Whitelabel theme.

 - Enable the module OpenEuropa Whitelabel Search (oe_whitelabel_search).
 - Go to section [Place the Search Box block](#place-the-search-box-block) if only the block is needed.
 - If content type is ready to be used, skip section `Create a content type Test`. For the instructions on the Search API and Views, go to next sections.

## Create a content type Test

* Go to Create content
* Click on Add content type button
* Add a new content type with data:
* Name: Test
* Machine name: test
* Click on Save and manage fields

## Create content

* User can install devel and use devel_generate module or create content manually.

## Create Server

* Go to Search API
* Click on Add server
* Create a Test server

## Create the index

* Go to Search API
* Click on Add index
* Index name will be Content
* Select Content at Datasources
* At Configure the Content Datasource
* Select option Only those selected
* Select Test Bundle
* Select Test Server
* Enabled field must be checked
* Click on Save button

## Add fields

* Go to Search API
* Edit the Content Index and navigate to the Fields tab
* Add fields:
  * Body
  * Changed
  * Published
  * Title
* Save changes.

## Index elements

* Go to Search API
* View the Content Index
* Depending if the content is already indexed or not:
  * Click on Index now
  * Clear all the indexed data and then click on Index now.


## Create a Search view

* Click on button Add view
* View name is Search
* In view settings select Index content
* In Page settings select Create a page with
  * Title: Search
  * Path: search
  * Leave the rest of elements as they are.
* Click on Save and edit

When at the main page of the view:
* At Fields add the indexed fields to the view.
* Reorder the fields to have Title, Body, Changed and Published
* At Header add Global: Result summary (this will be used to check the search later).
* At Advanced section in Contextual filters add a filter: Search: Fulltext search.
* Configure the filter as:
  * Select Provide default value
  * For type field: Select Query parameter
  * Query parameter must be set to search_api_fulltext
  * Fallback value: all
  * Parse mode: Multiple words
  * Operator: Contains any of these words
* Click on Apply button to set the filter
* Click on Save button to save the view

## Activate Autocomplete

* Go to  Search API
* On the Content index select Autocomplete
* Activate the Search View
* Click on Save button

## Place the Search Box block

* Go to Block Layout
* Click on Place block at the Header right section
* Uncheck the Display title
* Fill in fields with:
  * Form action: search (the path of the view page for Search)
  * Input name: search_api_fulltext (the name of the contextual filter at the view Search)
  * Input label: Search
  * Button label: Search
  * Button type: submit
  * Enable autocomplete: check the option
  * View id: search (the machine name of the Search view)
  * View display: page_1
