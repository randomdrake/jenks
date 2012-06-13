jenks
=====

Jenks natural breaks optimization in PHP.

The Jensk natural breaks optimization method allows you to break up data points into the best possible number of groupings, with the best possible contents of each group for Choroplethic mapping. 

http://en.wikipedia.org/wiki/Choropleth_map

Here's how it works (from http://en.wikipedia.org/wiki/Jenks_natural_breaks_optimization):

>The method requires an iterative process. That is, calculations must be repeated using different breaks in the dataset to determine which set of breaks has the smallest in-class variance. The process is started by dividing the ordered data into groups. Initial group divisions can be arbitrary. There are four steps that must be repeated:
>
>1. Calculate the sum of squared deviations between classes (SDBC).
>2. Calculate the sum of squared deviations from the array mean (SDAM).
>3. Subtract the SDBC from the SDAM (SDAM-SDBC). This equals the sum of the squared deviations from the class means.
>4. After inspecting each of the SDBC, a decision is made to move one unit from the class with the largest SDBC toward the class with the lowest SDBC.
>
>New class deviations are then calculated, and the process is repeated until the sum of the within class deviations reaches a minimal value.

![Jenks Algorithm](http://randomdrake.com/jenks.gif "Source - http://www.biomedware.com/files/documentation/spacestat/interface/map/classify/About_natural_breaks.htm")

*A is the set of values that have been ordered from 1 to N.
*1 ≤ i < j < N
*Mean i..j is the mean of the class bounded by i and j.

Soon to be released with an open source license.


I studied up on this and wrote this while working on a project a few years ago. I found that all of the available choroplethic mapping solutions available had inadequate splits of data when asked to create a map.

This was originally based on another script that was in French (which I don't speak). That one had bugs and was not very easy to understand.

This one works well and is flexible. It has been tested pretty thoroughly and, as far as I can tell, is correct.

I still need a chance to go over it again and make improvements and/or more comments. It's been a while.

-David Drake @randomdrake