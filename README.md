jenks
=====

PHP Implementation of Jenks Natural Breaks Optimization for Choropleth Mapping
-----

About:
=====
The Jenks natural breaks optimization method allows you to break up data points into the best possible number of groupings, with the best possible contents of each group for choropleth mapping. 

![Choroplethic Map](http://randomdrake.com/map.png "An example of a choropleth map.")

http://en.wikipedia.org/wiki/Choropleth_map

Here's how it works (from [Wikipedia](http://en.wikipedia.org/wiki/Jenks_natural_breaks_optimization)):

>The method requires an iterative process. That is, calculations must be repeated using different breaks in the dataset to determine which set of breaks has the smallest in-class variance. The process is started by dividing the ordered data into groups. Initial group divisions can be arbitrary. There are four steps that must be repeated:
>
>1. Calculate the sum of squared deviations between classes (SDBC).
>2. Calculate the sum of squared deviations from the array mean (SDAM).
>3. Subtract the SDBC from the SDAM (SDAM-SDBC). This equals the sum of the squared deviations from the class means.
>4. After inspecting each of the SDBC, a decision is made to move one unit from the class with the largest SDBC toward the class with the lowest SDBC.
>
>New class deviations are then calculated, and the process is repeated until the sum of the within class deviations reaches a minimal value.

![Jenks Algorithm](http://randomdrake.com/jenks.gif "Source - http://www.biomedware.com/files/documentation/spacestat/interface/map/classify/About_natural_breaks.htm")

or

![Jenks Algorithm Alternate](http://randomdrake.com/jenks2.gif "Source - http://www.biomedware.com/files/documentation/spacestat/interface/map/classify/About_natural_breaks.htm")

Where...
* A is the set of values that have been ordered from 1 to N.
* 1 ≤ i < j < N
* Mean i..j is the mean of the class bounded by i and j.

Development:
=====
I studied up on this and wrote it while working [for a startup](http://grupthinkpowered.com/) a few years ago, in 2009. I found that all of the available choroplethic mapping solutions available had inadequate splits of data when asked to create a map. Unfortunately, very few implementations of Jenks exist outside of professional cartography packages.

When re-writing this, I had chosen to use the Google Charts API for our maps so, you should be able to use this to simply output a map assuming you provide all the necessary parameters.

This was kind of based on [another script](http://www.forumsig.org/showthread.php?t=22055) that was in French (which I don't speak very well, at all). The original script had many issues and bugs and wasn't as flexible as I wanted it to be. So, I re-wrote it.

This one works well and is flexible in terms of the datasets. It has been tested pretty thoroughly and, as far as I can tell, is correct.

To Do:
=====
* Need a chance to go over it again and make improvements and/or more comments. 
* Bug test.
* Write example usage.
* See if the Google Maps stuff even works anymore.
* Make a blog post / homepage.

License:
=====
Open-source and free for use.
>Copyright 2012 David Drake
>
>Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
>
>http://www.apache.org/licenses/LICENSE-2.0
>
>Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License. 

Author:
=====
David Drake 

[@randomdrake](https://twitter.com/#!/randomdrake) | [http://randomdrake.com](http://randomdrake.com) | [LinkedIn](http://www.linkedin.com/pub/david-drake/52/247/465)
