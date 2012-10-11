#!/usr/bin/python

import sys
sys.path.append("../src")

from wx4funImage import wx4funMapImage

# Create new mapImage object. 
mapImage = wx4funMapImage()
# Set the filename.
mapImage.setFilename("2012-10-05T23-15-00.png")
# Set the latitude of this image - we could have a class for Rain and this is
# Set in the class itself.
mapImage.setLatCoords(48.0, 61.0)
mapImage.setLonCoords(-12.0, 5.0)
# Load the file
mapImage.loadFile()
# Load the file into memory
mapImage.loadImage()
# Load the bounding box into memory.
mapImage.loadPixelBounds()

lon = 0.

# Test the calculation of going from and to lat/lon and pixel.
#x = 0
#y = 0
#print x,y,mapImage.getLatLonFromPixel(x,y)
#x = 0
#y = 500
#print x,y,mapImage.getLatLonFromPixel(x,y)
#x = 500
#y = 0
#print x,y,mapImage.getLatLonFromPixel(x,y)
#x = 500
#y = 500
#print x,y,mapImage.getLatLonFromPixel(x,y)
#
#lon = -12.
#lat = 61.
#print lat,lon,mapImage.getPixelFromLatLon(lat,lon)
#lon = -12.
#lat = 48.
#print lat,lon,mapImage.getPixelFromLatLon(lat,lon)
#lon = 5.0
#lat = 61.
#print lat,lon,mapImage.getPixelFromLatLon(lat,lon)
#lon = 5.0
#lat = 48.
#print lat,lon,mapImage.getPixelFromLatLon(lat,lon)

#for lat in range(50,60):
#  val = mapImage.getIntensityFromLatLon(lat, lon)
#  print "Value at [{0},{1}] is {2}".format(lat, lon, val)
#mapImage.connectDatabase()
#mapImage.insertAllValuesInDatabase()
mapImage.printAllValues()
