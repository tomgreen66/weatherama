# This will create a class to record information about the image we have.

# Add some non-system loaded libraries
import sys
sys.path.append("/usr/lib64/python2.6/site-packages/SQLAlchemy-0.7.8-py2.6-linux-x86_64.egg")

# The Python Image Library can be used to access Images
from PIL import Image

# Accessing SQL alchemy
import sqlalchemy

# Start defining the class
class wx4funMapImage:
  '''A class to store all information required to access a image of a map.'''
  def __init__(self):
    
    '''This initialises the class'''
    '''Layer number to give this Image a "height"'''
    self._layer_number = 1
  def connectDatabase(self):
    '''This connects to the database using sqlalchemy'''
    databaseServer = "mysql://fahren:byte1@10.0.0.157/met"
    engine = sqlalchemy.create_engine(databaseServer)
    self._metadata = sqlalchemy.MetaData(bind=engine)
    self._daptable = sqlalchemy.Table("datapoints", self._metadata, 
                                      autoload=True)
    self._conn = engine.connect()
    '''Delete any existing datapoints in table'''
    self._conn.execute(self._daptable.delete())
    '''Start a transaction process''' 
    self._trans = self._conn.begin()
  def insertValueInDatabase(self, lat,lon,intensity):
    '''Inserts a value into a database'''
    '''Create new insert type.'''
    ins = self._daptable.insert()
    '''Create a new row with insert'''
    new_row = ins.values(x=lon, y=lat, z=self._layer_number,
                         type_id=1, intensity=intensity)
    '''Execute the instrection'''
    self._conn.execute(new_row)
    #DEBUG:
    #print lon, lat, intensity
  def insertAllValuesInDatabase(self):
    '''Insert all pixel values into database'''
    '''Loop from y1 to y0 as the top of the image is 0 and bottom is height'''
    for y in range(self._y1, self._y0):
      for x in range(self._x0, self._x1):
        latlon = self.getLatLonFromPixel(x,y)
        intensity = self.getIntensityFromPixel(x,y)
        if intensity > 0:
          self.insertValueInDatabase(latlon[0],latlon[1],intensity)
    self._trans.commit()
  def printAllValues(self):
    '''Print all pixel values'''
    for y in range(self._y1, self._y0):
      for x in range(self._x0, self._x1):
        latlon = self.getLatLonFromPixel(x,y)
        intensity = self.getIntensityFromPixel(x,y)
        rgba = self.getPixelValue(x,y)
        if intensity > 0:
          #print intensity, rgba 
          print latlon[1], latlon[0], intensity
  def setFilename(self, filename):
    '''Store filename'''
    self._filename = filename
  def setLatCoords(self, lat0, lat1):
    '''Set lat of the edges of map (lat0 southern boundary).'''
    self._lat0 = lat0
    self._lat1 = lat1
  def setLonCoords(self, lon0, lon1):
    '''Set lon of the edge of map (lon0 western boundary)'''
    self._lon0 = lon0
    self._lon1 = lon1
  def loadFile(self):
    '''Open the file'''
    self._im = Image.open(self._filename)
  def loadImage(self):
    '''Store the image into memory'''
    self._pix = self._im.load()
  def loadPixelBounds(self):
    '''Store the bounding box of the image.'''
    bbox     = self._im.getbbox()
    self._x0 = bbox[0]
    self._x1 = bbox[2]
    self._y0 = bbox[3]
    self._y1 = bbox[1]
  def getPixelBounds(self):
    '''Return the pixel boundaries'''
    return self._im.getbbox()
  def getPixelValue(self, x, y):
    '''Rerturn the pixel value at [x,y])'''
    return self._pix[x,y]
  def getLatLonFromPixel(self, x, y):
    '''Find the fraction where we are in the image pixel wise'''
    lonfrac = float(x - self._x0) / float(self._x1 - self._x0)
    latfrac = float(y - self._y0) / float(self._y1 - self._y0)
    '''Apply this fraction to the lat/lon dimensions.'''
    lon     = self._lon0 + lonfrac * (self._lon1 - self._lon0)
    lat     = self._lat0 + latfrac * (self._lat1 - self._lat0)
    return (lat,lon)
  def getPixelFromLatLon(self, lat, lon):
    '''Find the fraction where we are in the image lat/lon wise.'''
    xfrac = (lon - self._lon0) / (self._lon1 - self._lon0)
    yfrac = (lat - self._lat0) / (self._lat1 - self._lat0)
    '''Apply this fraction to the pixel coordinates'''
    x = int(self._x0 + xfrac * (self._x1 - self._x0))
    y = int(self._y0 + yfrac * (self._y1 - self._y0))
    return (x, y)

  def getPixelValueFromLatLon(self, lat, lon):
    pixel = self.getPixelFromLatLon
    return self.getPixelValue(pixel[0], pixel[1])
  def getIntensityFromLatLon(self, lat, lon):
    '''Return the intensity given a lat/lon coord.'''
    '''Find the RGBA value given a lat lon point'''
    rgba = self.getPixelValueFromLatLon(lat,lon)
    '''Use the RGBA value to calculate a arbitrary "intensity"'''
    return self.getIntensityFromRGBA(rgba)
  def getIntensityFromPixel(self, x, y):
    '''Return the intensity given a pixel coordinate'''
    '''Find the RGBA value given a pixel coordinate.'''
    rgba = self.getPixelValue(x, y)
    '''Use the RGBA value to calculate an arbitrary "intensity"'''
    return self.getIntensityFromRGBA(rgba)
  def getIntensityFromRGBA(self, rgba):
    '''Return the intensity value given an RGBA value.'''
    '''Translate array into easily understood variables'''
    red   = rgba[0]
    green = rgba[1]
    blue  = rgba[2]
    alpha = rgba[3]
    '''Set initial value to a missing data value'''
    intensity = 10
    '''Now start the check'''
    if alpha < 200:
      '''
      Since image uses transparancy to a alpha value of less than 200
      we assume ther is no rain.
      '''
      intensity = 0
    elif red > 200 and green > 200 and blue > 200:
      '''
      Since all colors are high we are near white - set very high value.
      '''
      intensity = 9
    elif red >= blue and red >= green:
      '''If red is dominant and much higher than green set high value'''
      if red > 1.5*green:
        intensity = 4
      else:
        '''Red and green are about the same set to 3.'''
        intensity = 3
    elif green >= blue and green >= red:
      '''If green is dominant then set to 2.'''
      intensity = 2
    elif blue >= red and blue >= green:
      '''If blue is dominant then set to 1.'''
      intensity = 1
    
    ''' Now hopefully return the intensity (and hopefully not 10)'''
    return intensity

